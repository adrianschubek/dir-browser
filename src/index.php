<?php

define('VERSION', '3.7.0');

define('PUBLIC_FOLDER', __DIR__ . '/public');

$[if `process.env.PASSWORD_RAW !== undefined || process.env.PASSWORD_HASH !== undefined`]$
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_USER'] !== '${{`process.env.PASSWORD_USER`}}$' || $_SERVER['PHP_AUTH_PW'] !== '${{`process.env.PASSWORD_RAW ?? "password_hash("+process.env.PASSWORD_HASH+", PASSWORD_DEFAULT)"`}}$') {
  header('WWW-Authenticate: Basic realm="dir-browser"');
  header('HTTP/1.0 401 Unauthorized');
  echo "Authentication required.";
  die;
}
$[end]$

$[if `process.env.TIMING`]$
$time_start = hrtime(true); 
$[end]$

$[if `process.env.README_RENDER === "true"`]$
require __DIR__ . "/vendor/autoload.php";
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\DisallowedRawHtml\DisallowedRawHtmlExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\MarkdownConverter;
$[end]$

function human_filesize($bytes, $decimals = 2): string
{
  $sz = ' KMGTP';
  $factor = floor((strlen($bytes) - 1) / 3);
  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor] . "B";
}

function numsize($size, $round = 2)
{
  if ($size === 0) return '0';
  $unit = ['', 'K', 'M', 'B', 'T'];
  return round($size / pow(1000, ($i = floor(log($size, 1000)))), $round) . $unit[$i];
}

// fix whitespace in path results in not found errors
$request_uri = rawurldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

$url_parts = array_filter(explode(separator: '/', string: $request_uri), fn ($part) => $part !== '');

// get real path and check if accessible (open_basedir)
$local_path = realpath(PUBLIC_FOLDER . $request_uri);
if (!str_starts_with($local_path, PUBLIC_FOLDER)) {
  goto skip; /* File should be ignored so skip to 404 */
} 

// check if path is dir
$path_is_dir = is_dir($local_path);

class File
{
  public string $name;
  public string $url;
  public string $size;
  public bool $is_dir;
  public string $modified_date;
  public int $dl_count;
  public ?object $meta;

  public function __toString(): string
  {
    return $this->name;
  }
}

/* @var array<File> */
$sorted = [];

$total_items = 0;
$total_size = 0;

/**
 * Check if file/folder should be hidden (ignored)
 * @param string $path full path to file or folder
 */
function hidden(string $path): bool {
  $[if `process.env.IGNORE !== undefined`]$
  $ignorePatterns = explode(';', "${{`process.env.IGNORE ?? ""`}}$");
  foreach ($ignorePatterns as $pattern) {
    if (preg_match("#" . $pattern . "#im", $path) === 1) {
      return true;
    }
  }
  $[end]$
  return false;
}

/**
 * Check if file/folder should be available to (batch) download.
 * Check exists, hidden, metadata...
 * @param string $path full path to file or folder
 * @param bool $includeProtected include files with password protection (for search only)
 * @return string|false path if available, false if not
 */
function available(string $user_path, bool $includeProtected = false): string | false {
  $path = realpath(PUBLIC_FOLDER . $user_path);
  if ($path === false || !str_starts_with($path, PUBLIC_FOLDER) || hidden($path)) return false;
  $[if `process.env.METADATA === "true"`]$
  if (str_contains($path, ".dbmeta.")) return false;
  $meta_file = realpath($path . '.dbmeta.json');
  if ($meta_file !== false) {
    $meta = json_decode(file_get_contents($meta_file));
    if (!isset($meta)) return true;
    // hidden check
    if (isset($meta->hidden) && $meta->hidden === true) return false;
    // if password or password_hash set reject 
    if (!$includeProtected && (isset($meta->password) || isset($meta->password_hash))) return false;
  }
  $[end]$
  return $path;
}

// for batch download
function getDeepUrlsFromArray(array $input_urls): array {
  $urls = [];
  foreach ($input_urls as $url) {
    if (($path = available($url)) !== false) {
      if (is_dir($path)) {
        // scan this folder. exclude special folders
        $deep_files = array_diff(scandir($path), ['.', '..']);
        // scandir returns all files with full filesystem path so strip PUBLIC_FOLDER
        $deep_urls = array_map(fn ($file) => substr($path . '/' . $file, strlen(PUBLIC_FOLDER)), $deep_files);
        // recursion
        $urls = array_merge($urls, getDeepUrlsFromArray($deep_urls));
      } else {
        $urls[] = $url;
      }
    }
  }
  return $urls;
}

$[if `process.env.SEARCH === "true"`]$
/**
 * Regex Search for files and folders in root_folder
 * @return array<File>
 */
function globalsearch(string $query, string $root_folder): array {
  $[end]$
  $[if `process.env.SEARCH === "true" && process.env.SEARCH_ENGINE === "regex"`]$
  $rdit = new RecursiveDirectoryIterator($root_folder, RecursiveDirectoryIterator::SKIP_DOTS);
  $rit = new RecursiveIteratorIterator($rdit);
  $rit->setMaxDepth(${{`process.env.SEARCH_MAX_DEPTH`}}$);
  $found = new RegexIterator($rit, "/$query/", RecursiveRegexIterator::MATCH);
  $[end]$
  $[if `process.env.SEARCH === "true" && process.env.SEARCH_ENGINE === "glob"`]$
  $found = new GlobIterator($root_folder . "/" . $query, FilesystemIterator::SKIP_DOTS);
  $[end]$
  $[if `process.env.SEARCH === "true"`]$
  $found = array_keys(iterator_to_array($found));
  $search_results = [];
  $found_counter = 0;
  foreach ($found as $path) {
    if ($found_counter >= ${{`process.env.SEARCH_MAX_RESULTS`}}$) break;
    if (($path = available(substr($path, strlen(PUBLIC_FOLDER)), true)) !== false) {
      // only paths are returned due to performance reasons
      $search_results[] = [
        "url" => substr($path, strlen(PUBLIC_FOLDER)),
        // strip base path from url
        "name" => substr($path, strlen($root_folder) + 1 /* slash */),
        "is_dir" => is_dir($path),
      ];
      $found_counter++;
      // $file_size = filesize($path);
      // $file_modified_date = gmdate('Y-m-d\TH:i:s\Z', filemtime($path));
      // $item = new File();
      // $item->name = basename($path);
      // $item->url = substr($path, strlen(PUBLIC_FOLDER));
      // $item->size = $file_size;
      // $item->is_dir = $is_dir;
      // $item->modified_date = $file_modified_date;
      // $item->dl_count = -1;
      // $search_results[] = $item;
    }
  }
  return [
    "results" => $search_results,
    "total" => count($search_results),
    "truncated" => $found_counter >= ${{`process.env.SEARCH_MAX_RESULTS`}}$,
    "base_folder" => substr($root_folder, strlen(PUBLIC_FOLDER))
  ];
}

// global search api
if (isset($_REQUEST["q"]) && $path_is_dir) {
  $search = $_REQUEST["q"];
  // start search from current folder
  $search_results = globalsearch($search, $local_path);
  header("Content-Type: application/json");
  die(json_encode($search_results));
}
$[end]$

$[if `process.env.BATCH_DOWNLOAD === "true"`]$
function downloadBatch(array $urls) {
  $total_size = 0;
  $zip = new ZipArchive();
  $zipname = tempnam(__DIR__ . "/tmp", 'db_batch_');
  unlink($zipname); // fixes https://stackoverflow.com/a/64698936
  $all_urls = getDeepUrlsFromArray($urls);

  $[end]$
  $[if `process.env.BATCH_DOWNLOAD === "true" && process.env.DOWNLOAD_COUNTER === "true"`]$
  $redis = new Redis();
  $redis->connect('127.0.0.1', 6379);
  $dl_counters = $redis->mget($all_urls);
  $new_dl_counters = [];
  for ($i = 0; $i < count($all_urls); $i++) $new_dl_counters[$all_urls[$i]] = $dl_counters[$i] + 1;
  $redis->mset($new_dl_counters);
  $[end]$
  $[if `process.env.BATCH_DOWNLOAD === "true"`]$
  try {
    $zip->open($zipname, ZipArchive::CREATE);
    foreach ($all_urls as $path) {
      // echo "Add file: " . PUBLIC_FOLDER . $path . "\n";
      // scandir if is_directory. create folders if necessary (done addFile)
      if (($fs = filesize(PUBLIC_FOLDER . $path)) > 1024 * 1024 * ${{`process.env.BATCH_MAX_FILE_SIZE`}}$) throw new Exception("File $file exceeds ${{`process.env.BATCH_MAX_FILE_SIZE`}}$ MB limit");
      $total_size += $fs;
      if ($total_size > 1024 * 1024 * ${{`process.env.BATCH_MAX_TOTAL_SIZE`}}$) throw new Exception("Total size of files exceeds ${{`process.env.BATCH_MAX_TOTAL_SIZE`}}$ MB limit");
      if (disk_free_space(dirname($zipname)) - $total_size < ${{`process.env.BATCH_MIN_SYSTEM_FREE_DISK`}}$) throw new Exception("Not enough space to create zip");
      // remove leading "/" bc windows cannot open zip
      if ($zip->addFile(PUBLIC_FOLDER . $path, substr($path, 1)) === false) throw new Exception("Something went wrong when adding $file to zip");
      $zip->setCompressionName($path, ZipArchive::CM_${{`process.env.BATCH_ZIP_COMPRESS_ALGO`}}$);
    }
    $zip->close();
    header('Content-Type: application/zip');
    header('Content-Length: ' . filesize($zipname));
    header('Content-Disposition: attachment; filename="' . bin2hex(random_bytes(8)) . '.zip"');
    readfile($zipname);
  } catch (\Throwable $th) {
    echo "Batch download error: " . $th->getMessage();
  } finally {
    unlink($zipname);
    die();
  }
}
$[end]$

// local path exists
if ($path_is_dir) {
  $[if `process.env.DOWNLOAD_COUNTER === "true"`]$
  $redis = new Redis();
  $redis->connect('127.0.0.1', 6379);
  $[end]$
  // TODO: refactor use MGET instead of loop GET

  // parent folder is hidden so skip
  if (hidden(substr($local_path, strlen(PUBLIC_FOLDER)))) {
    $path_is_dir = false;
    goto skip; /* Folder should be ignored so skip to 404 */
  }  

  $sorted_files = [];
  $sorted_folders = [];
  foreach (($files = scandir($local_path)) as $file) {
    // $relative_path. remove '/var/www/public' from path
    $url = substr($local_path, strlen(PUBLIC_FOLDER)) . '/' . $file;

    // always skip current folder '.' or parent folder '..' if current path is root or file should be ignored or .dbmeta.json
    if ($file === '.' || $file === '..' && count($url_parts) === 0 || $file !== '..' && hidden($url) $[if `process.env.METADATA === "true"`]$|| str_contains($file, ".dbmeta.")$[end]$) continue;

    $file_size = filesize($local_path . '/' . $file);

    $is_dir = is_dir($local_path . '/' . $file);

    $file_modified_date = gmdate('Y-m-d\TH:i:s\Z', filemtime($local_path . '/' . $file));

    $[if `process.env.METADATA === "true"`]$
    // load metadata if file exists
    $meta_file = realpath($local_path . '/' . $file . '.dbmeta.json');
    if ($meta_file !== false) {
      $meta = json_decode(file_get_contents($meta_file));
      if ($meta !== null && $meta->hidden === true) continue;
    } else {
      // Variables stay alive in php so we need to reset it explicitly
      $meta = null;
    }
    $[end]$

    $item = new File();
    $item->name = $file;
    $item->url = $url;
    $item->size = $file_size;
    $item->is_dir = $is_dir;
    $item->modified_date = $file_modified_date;
    $item->dl_count =  $[if `process.env.DOWNLOAD_COUNTER === "true"`]$!$is_dir ? $redis->get($url) :$[end]$ 0;
    $item->meta = $meta ?? null;
    if ($is_dir) {
      array_push($sorted_folders, $item);
    } else {
      array_push($sorted_files, $item);
    }

    // don't count parent folder
    if ($file !== "..") $total_items++;
    $total_size += $file_size;
  }

  natcasesort($sorted_folders);
  natcasesort($sorted_files);
  $[if `process.env.REVERSE_SORT`]$
  $sorted_folders = array_reverse($sorted_folders);
  $sorted_files = array_reverse($sorted_files);
  $[end]$
  $sorted = array_merge($sorted_folders, $sorted_files);

  // if list request return json
  $[if `process.env.API === "true"`]$
  if(isset($_REQUEST["ls"])) {
    $info = [];
    foreach ($sorted as $file) {
      if ($file->name === "..") continue; // skip parent folder
      $info[] = [
        "url" => $file->url,
        "name" => $file->name,
        "type" => $file->is_dir ? "dir" : "file",
        "size" => intval($file->size),
        "modified" => $file->modified_date,
        "downloads" => ${{`process.env.DOWNLOAD_COUNTER === "true" ? "intval($redis->get($file->url))" : "0"`}}$
      ];
    }
    header("Content-Type: application/json");
    die(json_encode($info));
  }
  $[end]$

  // is batch download?
  $[if `process.env.BATCH_DOWNLOAD === "true"`]$
  if (isset($_POST["download_batch"])) { // does not work with nested download folders
    downloadBatch($_POST["download_batch"]);
    die();
  }
  $[end]$

  // readme
  $[if `process.env.README_RENDER === "true"`]$
  // check if readme exists
  foreach (explode(';', "${{`process.env.README_NAME`}}$") as $readme_name) {
    foreach ($sorted_files as $file) {
      if (mb_strtolower($file->name) === $readme_name) {
        $readme = $file;
        break 2;
      }
    }
  }
  $[end]$

  $[if `process.env.README_RENDER === "true" && process.env.README_META === "true"`]$
  // check if ".dbmeta.md" exists. overwrite previous readme
  if (file_exists($local_path . '/.dbmeta.md')) {
    $readme = new File();
    $readme->url = substr($local_path, strlen(PUBLIC_FOLDER)) . '/.dbmeta.md';
  }
  $[end]$

  $[if `process.env.README_RENDER === "true"`]$
  if ($readme) {
    $config = [];

    $environment = new Environment($config);
    $environment->addExtension(new CommonMarkCoreExtension());

    $environment->addExtension(new AutolinkExtension());
    ${{`!process.env.ALLOW_RAW_HTML ? "$environment->addExtension(new DisallowedRawHtmlExtension());" : ""`}}$ 
    $environment->addExtension(new StrikethroughExtension());
    $environment->addExtension(new TableExtension());
    $environment->addExtension(new TaskListExtension());
    $converter = new MarkdownConverter($environment);

    $readme_render = $converter->convert(file_get_contents(PUBLIC_FOLDER . $readme->url));
  }
  $[end]$

} elseif (file_exists($local_path)) {
  // local path is file. serve it directly using nginx

  $relative_path = substr($local_path, strlen(PUBLIC_FOLDER));

  if (hidden($relative_path)) {      
    goto skip; /* File should be ignored so skip to 404 */
  }

  $[if `process.env.METADATA === "true"`]$
  // skip if file is .dbmeta.json
  if (str_contains($local_path, ".dbmeta.json")) goto skip;

  // check if password proteced
  if (file_exists($local_path . '.dbmeta.json')) {
    $meta = json_decode(file_get_contents($local_path . '.dbmeta.json'));
    if (isset($meta->password_hash)) {
      if (!isset($_REQUEST["key"]) || !password_verify($_REQUEST["key"], $meta->password_hash)) {
        http_response_code(401);
        define('AUTH_REQUIRED', true);
        goto end;
      }
    }
    if (isset($meta->password)) {
      if (!isset($_REQUEST["key"]) || $_REQUEST["key"] !== $meta->password) { // allows get and post reqeusts
        http_response_code(401);
        define('AUTH_REQUIRED', true);
        goto end;
      }
    }
  }
  $[end]$

  $[if `process.env.HASH`]$
  // only allow download if requested hash matches actual hash
  if (${{`process.env.HASH_REQUIRED === "true" ? "true ||" : ""`}}$ isset($_REQUEST["hash"]) || isset($meta) && $meta->hash_required === true) {
    if ($_REQUEST["hash"] !== hash_file('${{`process.env.HASH_ALGO`}}$', $local_path)) {
      http_response_code(403);
      die("<b>Access denied.</b> Supplied hash does not match actual file hash.");
    }
  }
  $[end]$

  // increment redis view counter
  $[if `process.env.DOWNLOAD_COUNTER === "true"`]$
  $redis = new Redis();
  $redis->connect('127.0.0.1', 6379);
  $[end]$

  $[if `process.env.API === "true"`]$
  if(isset($_REQUEST["info"])) {
    $info = [
      "url" => $relative_path, // FIXME: use host domain! abc.de/foobar
      "name" => basename($local_path),
      "mime" => mime_content_type($local_path) ?? "application/octet-stream",
      "size" => filesize($local_path),
      "modified" => filemtime($local_path),
      "downloads" => ${{`process.env.DOWNLOAD_COUNTER === "true" ? "intval($redis->get($file->url))" : "0"`}}$,
      "hash_${{`process.env.HASH_ALGO`}}$" => ${{`process.env.HASH === "true" ? "hash_file('"+process.env.HASH_ALGO+"', $local_path)" : "null"`}}$
    ];
    header("Content-Type: application/json");
    die(json_encode($info));
  }
  $[end]$

  $[if `process.env.DOWNLOAD_COUNTER === "true"`]$
  $redis->incr($relative_path);
  $[end]$
   
  // let nginx guess content type
  header("Content-Type: ");
  // let nginx handle file serving
  header("X-Accel-Redirect: /__internal_public__" . $relative_path);
  die();
} else {
  // local path does not exist
skip:
  http_response_code(404);
end: 
}
?>
<!doctype html>
<html lang="en" data-bs-theme="light">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="turbo-cache-control" content="no-cache">
  $[ifeq env:TRANSITION true]$
  <meta name="turbo-refresh-method" content="morph">
  <meta name="view-transition" content="same-origin" />
  $[end]$
  <title>Dir Browser - <?= '/' . implode(separator: '/', array: $url_parts) ?></title>
  $[ifeq env:THEME cerulean]$
  <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/cerulean/bootstrap.min.css" rel="stylesheet" data-turbo-eval="false">
  $[ifeq env:THEME materia]$
  <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/materia/bootstrap.min.css" rel="stylesheet" data-turbo-eval="false">
  $[ifeq env:THEME quartz]$
  <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/quartz/bootstrap.min.css" rel="stylesheet" data-turbo-eval="false">
  $[ifeq env:THEME sandstone]$
  <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/sandstone/bootstrap.min.css" rel="stylesheet" data-turbo-eval="false">
  $[ifeq env:THEME sketchy]$
  <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/sketchy/bootstrap.min.css" rel="stylesheet" data-turbo-eval="false">
  $[ifeq env:THEME united]$
  <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/united/bootstrap.min.css" rel="stylesheet" data-turbo-eval="false">
  $[ifeq env:THEME yeti]$
  <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/yeti/bootstrap.min.css" rel="stylesheet" data-turbo-eval="false">
  $[ifeq env:THEME litera]$
  <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/litera/bootstrap.min.css" rel="stylesheet" data-turbo-eval="false">
  $[else]$
  <link href="${{`process.env.THEME_URL !== undefined ? process.env.THEME_URL : "https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/cosmo/bootstrap.min.css"`}}$" rel="stylesheet" data-turbo-eval="false">
  $[end]$
  $[ifeq env:README_RENDER true]$
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/adrianschubek/dir-browser@main/assets/readme/gh.css" data-turbo-eval="false"/>
  $[end]$
  <style data-turbo-eval="false">
    $[ifeq env:TRANSITION true]$
    html[data-turbo-visit-direction="forward"]::view-transition-old(sidebar):only-child {
      animation: normal 0.1s cubic-bezier(1, 0, 0, 1);
    }
    $[end]$
    $[ifeq env:THEME default]$
    [data-bs-theme=dark] {
      --bs-body-bg: #0d1117;
      --bs-secondary-bg: #000000;
      --bs-tertiary-bg: #000000;
      --bs-tertiary-bg-rgb: 0, 0, 0;
      #filetree > a:hover, #resultstree > a:hover {
        background-color: #ffffff0d;
      }
    }
    .dropdown-menu {
      border-radius: 5px;
      --bs-dropdown-border-width: 2px;
    }
    @media (min-width: 576px) {
      .dropdown:hover > .dropdown-menu {
        display: relative;
        margin-top: 0;
      }
    } /* FIXME breakes dropstart */
    $[end]$
    .drop-toggle {
      padding: 0 8px 0 8px;
      border-radius: 5px;
    }
    .item {
      grid-auto-flow: column dense;
      grid-template-columns: 20px auto 100px 75px max-content;
    }
    @media screen and (max-width: 768px) {
      .item {
        grid-auto-flow: column dense;
        grid-template-columns: 20px auto 0 0 0;
      }
    }
    .icon:before {
      font-size: 18px !important;
      width: 18px !important;
    }
    .icon {
      width: 24px !important;
      height: 24px !important;
      text-align: center;
    }
    body {
      overflow-y: scroll; /* prevents content shifting when scrollbar gets (in)visible */
      background-color: var(--bs-secondary-bg);
    }
    .footer {
      color: var(--bs-tertiary-color)
    }
    .db-row {
      text-decoration: unset;
      /* background-color: var(--bs-tertiary-bg); */
    }
    a {
      color: inherit;
      text-decoration: none;
    }
    #path > a:hover {
      text-decoration:underline;
      text-decoration-style: dotted;
    }
    #path > a:last-child {
      font-weight: bold;
    }
    #filetree > a:hover, #resultstree > a:hover {
      background-color: var(--bs-tertiary-bg);
    }
    #filetree > a, #resultstree > a {
      border-bottom: var(--bs-border-width) var(--bs-border-style) var(--bs-border-color) !important;
    }
    #filetree > a:last-child, #resultstree > a:last-child {
      border-bottom: none !important;
    }
    #sort > a > svg {
      width: 16px !important;
      height: 16px !important;
      display: none;
    }
    #sort > a:hover > svg {
      display: inline;
    }
    a[data-file-selected="1"] {
      background-color: var(--bs-primary-bg-subtle);
    }
  </style>
  $[if `process.env.ICONS !== "false"`]$
  <link data-turbo-eval="false" href="https://cdn.jsdelivr.net/npm/file-icons-js@1/css/style.min.css" rel="stylesheet"></link>
  $[end]$
  <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0/dist/turbo.es2017-umd.min.js"></script>
  <script data-turbo-eval="false">    /* fixes white flash */
    const getPreferredTheme = () => {
      if (localStorage.getItem('theme')) {
        return localStorage.getItem('theme')
      }
      return window.matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light'
    }

    const setTheme = (theme) => {
      if (theme === 'auto' && window.matchMedia('(prefers-color-scheme: dark)').matches) {
        document.documentElement.setAttribute('data-bs-theme', 'dark')
      } else {
        document.documentElement.setAttribute('data-bs-theme', theme);
      }
    }

    setTheme(getPreferredTheme())

    window.matchMedia('(prefers-color-scheme: dark)').addEventListener('change', () => {
      if (storedTheme !== 'light' || storedTheme !== 'dark') {
        setTheme(getPreferredTheme())
      }
    })

    function toggletheme() {
      const theme = getPreferredTheme() === 'dark' ? 'light' : 'dark'
      console.log("click set to " + theme);
      document.querySelector("[data-bs-theme]").setAttribute('data-bs-theme', theme)
      localStorage.setItem('theme', theme)
      setTheme(theme)
    }    
  </script>
</head>

<body class="d-flex flex-column min-vh-100">
  $[if `process.env.README_RENDER === "true" && process.env.README_FIRST === "true"`]$
    <?php
      if (isset($readme_render)) {
    ?>
    <div class="container pt-3">
      <div class="card rounded border-2 p-3 markdown-body-light markdown-body-dark" id="readme">
        <?= $readme_render ?>
      </div>
    </div>
    <?php 
    }
    ?>
  $[end]$
  <div class="container py-3">    
    <?php if (defined("AUTH_REQUIRED")) { ?>
      <div class="card rounded border-2 m-auto" style="max-width: 500px;">
        <div class="card-body">
          <h4 class="alert-heading key-icon">Protected file</h4>
          <p class="mb-2">Please enter the password to access this file.</p>
          <form method="post">
            <input autofocus type="password" class="form-control mb-2 rounded" id="key" name="key" required>
            <button type="submit" class="btn rounded btn-primary key-icon form-control">Continue</button>
          </form>
        </div>
      </div>
    <?php } else if (!$path_is_dir) { ?>
      <div class="card rounded border-2 m-auto" style="max-width: 500px;">
        <div class="card-body text-center">
          <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-unknown" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
            <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
            <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
            <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"></path>
            <path d="M12 17v.01"></path>
            <path d="M12 14a1.5 1.5 0 1 0 -1.14 -2.474"></path>
          </svg>
          Not Found<br>
          <a class="btn rounded btn-secondary mt-2" href="${{`process.env.BASE_PATH ?? ''`}}$/">Back to Home</a>
        </div>
      </div>

    <?php } else { ?>
      <div class="rounded container position-sticky card border-2 px-3" style="top:0; z-index: 5;border-bottom-left-radius: 0 !important;border-bottom-right-radius: 0 !important;">
        <div class="row db-row py-2 text-muted">          
          <div class="col" id="path">
            <a href="${{`process.env.BASE_PATH ?? ''`}}$/">/</a><?php
            // create links e.g. from ["foo","bar","foobar"] to ["/foo", "/foo/bar", "/foo/bar/foobar"]
            $urls = [];
            foreach ($url_parts as $i => $part) {
              $urls[] = end($urls) . '/' . $part;
              // var_dump($i, $part, $urls);
              echo '<a style="vertical-align: middle;" href="${{`process.env.BASE_PATH ?? ''`}}$' . $urls[$i - 1] . '/">' . $part . '/</a>';
            }
            ?>
          </div>
          <div class="col-auto pe-0">
            $[if `process.env.BATCH_DOWNLOAD === "true"`]$
            <a class="btn rounded btn-sm text-muted multiselect" onclick="downloadMultiple()" title="Download batch">
            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-download"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg>
            </a>
            <a class="btn rounded btn-sm text-muted" onclick="toggleMultiselect()" title="Multiple select">
            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-copy-check"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path stroke="none" d="M0 0h24v24H0z" /><path d="M7 9.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 16.737a2 2 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /><path d="M11 14l2 2l4 -4" /></svg>
            </a>
            <a class="btn rounded btn-sm text-muted" onclick="downloadThisFolder('<?= $request_uri ?>')" title="Download this folder">
            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-folder-down"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 19h-7a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2h4l3 3h7a2 2 0 0 1 2 2v3.5" /><path d="M19 16v6" /><path d="M22 19l-3 3l-3 -3" /></svg>
            </a>
            $[end]$
            $[if `process.env.SEARCH === "true"`]$
            <a class="btn rounded btn-sm text-muted" onclick="toggleSearch()" title="Search in <?= $request_uri ?>">
            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-search"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M10 10m-7 0a7 7 0 1 0 14 0a7 7 0 1 0 -14 0" /><path d="M21 21l-6 -6" /></svg>
            </a>
            $[end]$
            <a class="btn rounded btn-sm text-muted" data-color-toggler onclick="toggletheme()" title="Darkmode / Lightmode">
            <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-moon"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 3c.132 0 .263 0 .393 0a7.5 7.5 0 0 0 7.92 12.446a9 9 0 1 1 -8.313 -12.454z" /></svg>
            </a>
          </div>
        </div>
        <div class="row db-row py-2 text-muted d-none" id="search-container">
          <div class="col">
            <input type="text" class="form-control rounded" placeholder="Search in <?= $request_uri ?>*" id="search">
          </div>
        </div>
        <div class="row db-row py-2 text-muted" id="sort">
          $[if `process.env.BATCH_DOWNLOAD === "true"`]$
          <div class="col col-auto multiselect" style="display:none">
            <input id="selectall" class="form-check-input" style="padding:5px" type="checkbox" id="checkboxNoLabel" aria-label="..." />
            <!-- (un)select all -->
          </div>
          $[end]$
          <a href="" class="col" id="name">Name<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-arrows-sort"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 9l4 -4l4 4m-4 -4v14" /><path d="M21 15l-4 4l-4 -4m4 4v-14" /></svg></a>
          $[if `process.env.DOWNLOAD_COUNTER === "true"`]$<a href="" class="col col-auto text-end d-none d-md-inline-block" id="dl">Downloads<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-arrows-sort"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 9l4 -4l4 4m-4 -4v14" /><path d="M21 15l-4 4l-4 -4m4 4v-14" /></svg></a>$[end]$
          <a href="" class="col col-2 text-end d-none d-md-inline-block" id="size">Size<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-arrows-sort"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 9l4 -4l4 4m-4 -4v14" /><path d="M21 15l-4 4l-4 -4m4 4v-14" /></svg></a>
          <a href="" class="col col-2 text-end d-none d-md-inline-block" id="mod">Modified<svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-arrows-sort"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M3 9l4 -4l4 4m-4 -4v14" /><path d="M21 15l-4 4l-4 -4m4 4v-14" /></svg></a>
          <div class="col col-auto text-end d-none d-md-inline-block" id="mod">Actions</div>
        </div>
      </div>
      <div class="rounded container card border-2 px-3 d-none" style="border-top: none !important;border-top-right-radius: 0 !important;border-top-left-radius: 0 !important;" id="resultstree"></div>
      <div class="rounded container card border-2 px-3" style="border-top: none !important;border-top-right-radius: 0 !important;border-top-left-radius: 0 !important;" id="filetree">
        
        <?php
        $now = new DateTime();
        foreach ($sorted as $file) {
          $fileDate = new DateTime($file->modified_date);
          $diff = $now->diff($fileDate)->days;
        ?>
        <a data-turbo-action="advance" data-file-selected="0" data-file-isdir="<?= $file->is_dir ? "1" : "0" ?>" data-file-name="<?= $file->name ?>" data-file-dl="$[if `process.env.DOWNLOAD_COUNTER === "true"`]$<?= $file->dl_count ?>$[end]$" data-file-size="<?= $file->size ?>" data-file-mod="<?= $file->modified_date ?>"  href="${{`process.env.BASE_PATH ?? ''`}}$<?= $file->url ?><?= /* extra slash for dirs */ $file->is_dir ? "/" : "" ?>" class="row db-row py-2 db-file">
          <div class="col col-auto multiselect" style="display:none">
            <input class="form-check-input" style="padding:5px;pointer-events:none" type="checkbox" aria-label="..." />
          </div>
          <div class="col col-auto pe-0">
          <?php if ($file->name === "..") { ?>
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-corner-left-up" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path d="M18 18h-6a3 3 0 0 1 -3 -3v-10l-4 4m8 0l-4 -4"></path>
              </svg>
            <?php } elseif ($file->is_dir) { ?>
              <div class="dir-icon-placeholder" dirname="<?= $file->name ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-folder-filled" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                  <path d="M9 3a1 1 0 0 1 .608 .206l.1 .087l2.706 2.707h6.586a3 3 0 0 1 2.995 2.824l.005 .176v8a3 3 0 0 1 -2.824 2.995l-.176 .005h-14a3 3 0 0 1 -2.995 -2.824l-.005 -.176v-11a3 3 0 0 1 2.824 -2.995l.176 -.005h4z" stroke-width="0" fill="currentColor"></path>
                </svg>
              </div>
            <?php } else { ?>
              <div class="file-icon-placeholder" filename="<?= $file->name ?>">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                  <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
                  <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"></path>
                </svg>
              </div>
            <?php } ?>
            </div> 
            <div class="col">
            <?= $file->name ?>
            <?php 
              if ($file->meta !== null) {
                if ($file->meta->description !== null) {
            ?> 
                  <span class="text-body-secondary"><?= $file->meta->description ?></span>
            <?php
                }
                foreach ($file->meta->labels as $lbl) {
                  $l = explode(":", $lbl, 2);
            ?>
                  <span class="badge bg-<?= $l[0] ?>"><?= $l[1] ?></span>
            <?php
                }
                if ($file->meta->password !== null || $file->meta->password_hash !== null) {
            ?>
              <span title="Password protected" class="key-icon"></span>
            <?php
                }
              }
            ?>
            </div>
            <?php if (!$file->is_dir) { ?>
            <div class="col col-auto text-end">
              $[if `process.env.DOWNLOAD_COUNTER === "true"`]$
              <span title="Total downloads" class="text-muted ms-auto d-none d-md-inline rounded-1 text-end px-1 <?= $file->dl_count === 0 ? "text-body-tertiary" : "" ?>">
                <?= numsize($file->dl_count) ?>
                <svg style="margin-top: -5px;" xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-download" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                  <path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2"></path>
                  <path d="M7 11l5 5l5 -5"></path>
                  <path d="M12 4l0 12"></path>
                </svg>
              </span>
              $[end]$
            </div>
            <div class="col col-2 text-end">    
              <span title="File size" class="ms-auto d-none d-md-inline rounded-1 text-end px-1">
                <?= human_filesize($file->size) ?>
              </span>
            </div>
            <?php } ?>
            <div class="col col-2">
              <span title="Last modified on <?= $file->modified_date ?>" class="d-none d-md-block text-end filedatetime" ${{`process.env.HIGHLIGHT_UPDATED !== "false" && 'style="font-weight:<?= ($diff > 2 ? "normal !important;": "bold !important;") ?>"'`}}$>
                <?= $file->modified_date ?>
              </span>
            </div>
            <div class="col col-auto stopprop">
              <div class="btn-group dropdown dropstart">
                <button class="btn drop-toggle btn-small" type="button" data-bs-toggle="dropdown" aria-expanded="false"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="currentColor"  class="icon icon-tabler icons-tabler-filled icon-tabler-info-circle"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 2c5.523 0 10 4.477 10 10a10 10 0 0 1 -19.995 .324l-.005 -.324l.004 -.28c.148 -5.393 4.566 -9.72 9.996 -9.72zm0 9h-1l-.117 .007a1 1 0 0 0 0 1.986l.117 .007v3l.007 .117a1 1 0 0 0 .876 .876l.117 .007h1l.117 -.007a1 1 0 0 0 .876 -.876l.007 -.117l-.007 -.117a1 1 0 0 0 -.764 -.857l-.112 -.02l-.117 -.006v-3l-.007 -.117a1 1 0 0 0 -.876 -.876l-.117 -.007zm.01 -3l-.127 .007a1 1 0 0 0 0 1.986l.117 .007l.127 -.007a1 1 0 0 0 0 -1.986l-.117 -.007z" /></svg></button>
                <ul class="dropdown-menu">
                  <!-- <li><a class="dropdown-item stopprop" onclick="actionDownload()">Download</a></li> -->
                  <!-- <li><span class="dropdown-item stopprop">Download</span></li> -->
                  $[if `process.env.API === "true"`]$
                  <li><span class="dropdown-item stopprop" onclick="window.open('${{`process.env.BASE_PATH ?? ''`}}$<?= $file->url ?>?<?= $file->is_dir ? "ls" : "info"?>')"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-external-link"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 6h-6a2 2 0 0 0 -2 2v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2 -2v-6" /><path d="M11 13l9 -9" /><path d="M15 4h5v5" /></svg>API</span></li>
                  <hr style="margin:0;margin-top: 2px;margin-bottom: 2px;"/>
                  $[end]$
                  <li><span class="dropdown-item stopprop <?= $file->is_dir ? "disabled" : ""?>" onclick="getHashViaApi('${{`process.env.BASE_PATH ?? ''`}}$<?= $file->url ?>?info')"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-copy"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg> Hash</span></li>
                  <li><span class="dropdown-item stopprop" onclick="navigator.clipboard.writeText('<?= $file->name ?>')"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-copy"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg> Filename</span></li>
                  <li><span class="dropdown-item stopprop" onclick="navigator.clipboard.writeText('<?= $file->dl_count ?>')"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-copy"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg> Downloads</span></li>
                  <li><span class="dropdown-item stopprop" onclick="navigator.clipboard.writeText('${{`process.env.BASE_PATH ?? ''`}}$<?= $file->url ?>')"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-copy"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg> URL</span></li>
                  <li><span class="dropdown-item stopprop" onclick="navigator.clipboard.writeText('<?= $file->modified_date ?>')"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-copy"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg> Date</span></li>
                </ul>
              </div>
            </div>
          </a>

        <?php
        }
        ?>

        <?php if (count($sorted_files) === 0 && (count($sorted_folders) === 0 || count($sorted_folders) === 1 && $sorted_folders[0]->name === "..")) { ?>
          <div class="list-group-item text-center py-3" role="alert">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-folder-off" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
              <path d="M3 3l18 18"></path>
              <path d="M19 19h-14a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 1.172 -1.821m3.828 -.179h1l3 3h7a2 2 0 0 1 2 2v8"></path>
            </svg>
            Empty Folder
          </div>
        <?php } ?>
      </div>
    <?php } ?>
  </div>

  $[if `process.env.README_RENDER === "true" && process.env.README_FIRST === "false"`]$
  <?php
    if (isset($readme_render)) {
  ?>
  <div class="container pb-3">
    <div class="card rounded border-2 p-3 markdown-body-light markdown-body-dark" id="readme">
      <?= $readme_render ?>
    </div>
  </div>
  <?php 
  }
  ?>
  $[end]$

  <div class="mt-auto">
    <div class="container py-2 text-center" id="footer">
      <?= $total_items ?> Items | <?= human_filesize($total_size) ?> $[if `process.env.TIMING === "true"`]$| <?= (hrtime(true) - $time_start)/1000000 ?> ms $[end]$$[if `process.env.API === "true"`]$| <a href="<?= '/' . implode(separator: '/', array: $url_parts) . '?ls' ?>" target="_blank"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-api"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 13h5" /><path d="M12 16v-8h3a2 2 0 0 1 2 2v1a2 2 0 0 1 -2 2h-3" /><path d="M20 8v8" /><path d="M9 16v-5.5a2.5 2.5 0 0 0 -5 0v5.5" /></svg></a>$[end]$<br>
    <span style="opacity:0.8"><span style="opacity: 0.8;">Powered by</span>  <a href="https://dir.adriansoftware.de" class="text-decoration-none text-primary" target="_blank">dir-browser</a> v<?= VERSION ?></span>  
    </div>
  </div>

  $[if `process.env.LAYOUT === "popup" || process.env.LAYOUT === "full"`]$
  <div class="modal rounded fade show" style="background-color:rgba(0, 0, 0, 0.2);" id="file-popup" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable">
      <div class="modal-content rounded border-2">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="staticBackdropLabel">Modal title</h1>
          <button type="button" class="btn-close" id="file-popup-x" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          ...
        </div>
        <div class="modal-footer">
        ${{`process.env.API === "true" ? '<a id="file-info-url-api" href="" type="button" class="btn rounded btn-secondary" data-bs-dismiss="modal">API <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-code"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 8l-4 4l4 4" /><path d="M17 8l4 4l-4 4" /><path d="M14 4l-4 16" /></svg></a>' : ''`}}$
          <a id="file-info-url" type="button" class="btn rounded btn-primary">Download <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-download"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg></a>
        </div>
      </div>
    </div>
  </div>
  $[end]$

  <!-- Powered by https://github.com/adrianschubek/dir-browser -->
  <script data-turbo-eval="false">
    $[if `process.env.DATE_FORMAT === "relative"`]$
    function getRelativeTimeString(date, lang = navigator.language) {
      const timeMs = typeof date === "number" ? date : date.getTime();
      const deltaSeconds = Math.round((timeMs - Date.now()) / 1000);

      const cutoffs = [60, 3600, 86400, 86400 * 7, 86400 * 30, 86400 * 365, Infinity];
      const units = ["second", "minute", "hour", "day", "week", "month", "year"];

      // Find the ideal cutoff unit by iterating manually
      let unitIndex = 0;
      while (unitIndex < cutoffs.length && Math.abs(deltaSeconds) >= cutoffs[unitIndex]) {
        unitIndex++;
      }

      // Calculate the time difference in the current unit
      const timeInCurrentUnit = Math.abs(deltaSeconds) / (unitIndex ? cutoffs[unitIndex - 1] : 1);

      // Adjust the displayed time based on the 50% threshold
      const adjustedTime = Math.floor(timeInCurrentUnit);

      // Include the negative sign for time that has passed
      const sign = deltaSeconds < 0 ? "-" : "";

      const rtf = new Intl.RelativeTimeFormat(lang, { numeric: "auto" });
      return rtf.format(sign + adjustedTime, units[unitIndex]);
    }
    $[end]$

    $[if `process.env.HASH === "true"`]$
    // via api bc otherwise we need to include the hash in the tree itself which is costly
    const getHashViaApi = async (url) => {
      await fetch(url)
        .then(response => response.json())
        .then(data => {
          navigator.clipboard.writeText(data.hash_${{`process.env.HASH_ALGO`}}$);
        });
    }
    $[end]$

    // Batch download
    $[if `process.env.BATCH_DOWNLOAD === "true"`]$
    const download = async (all) => {
      // create form and submit
      const form = document.createElement('form');
      form.method = 'POST';
      form.action = '${{`process.env.BASE_PATH ?? ''`}}$';
      form.style.display = 'none';
      document.querySelectorAll('.db-file').forEach((file) => {
        if ((all || file.getAttribute('data-file-selected') === "1") && file.getAttribute('data-file-name') !== "..") {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'download_batch[]';
          input.value = file.getAttribute('href');
          form.appendChild(input);          
        }
      });
      document.body.appendChild(form);
      form.submit();
      document.body.removeChild(form);
    }
    const downloadThisFolder = async (path) => {
      console.log("Download this folder " + path);
      await download(true);
    }
    const downloadMultiple = async () => {
      console.log("Download multiple");
      await download(false);
    }
    const toggleMultiselect = () => {
      const local = localStorage.getItem("multiSelectMode");
      let multiSelectMode = local === null ? false : local === "true";
      multiSelectMode = !multiSelectMode;
      updateMultiselect(multiSelectMode);
      localStorage.setItem("multiSelectMode", multiSelectMode);
    }
    const toggleSelectAll = (e) => {
      if (e.target.checked) {
        document.querySelectorAll('.db-file').forEach((file) => {
          if (file.getAttribute('data-file-name') !== "..") {
            file.setAttribute("data-file-selected", "1");
            file.querySelector('input').checked = true; /* checkbox */
          }
        });
      } else {
        document.querySelectorAll('.db-file').forEach((file) => {
          if (file.getAttribute('data-file-name') !== "..") {
            file.setAttribute("data-file-selected", "0");
            file.querySelector('input').checked = false; /* checkbox */
          }
        });
      }
    }
    const dbItemClickListener = async (e) => {
      e.preventDefault();
      const file = e.target.closest('a');
      console.log(file.getAttribute("href"), file.getAttribute("data-file-name"))
      if (file.getAttribute("data-file-selected") === "1") {
        file.setAttribute("data-file-selected", "0");
        file.querySelector('input').checked = false; /* checkbox */
      } else {
        file.setAttribute("data-file-selected", "1");
        file.querySelector('input').checked = true;
      }
    }
    const updateMultiselect = (multi) => {
      if (multi) document.querySelector("#selectall").addEventListener('change', toggleSelectAll);
      else document.querySelector("#selectall").removeEventListener('change', toggleSelectAll);
      const selects = document.querySelectorAll('.multiselect');
      const files = document.querySelectorAll('.db-file');
      selects.forEach((select) => {
        if (multi) {
          select.style.display = 'inline-block';
        } else {
          select.style.display = 'none';
        }
      });
      files.forEach((file) => {
        // skip ".." folder
        if (file.getAttribute('data-file-name') === "..") {
          return;
        }
        // disable link
        if (multi) {
          // file.setAttribute("data-file-selected", "1")
          file.addEventListener('click', dbItemClickListener);
        } else {
          // file.setAttribute("data-file-selected", "0")
          file.removeEventListener('click', dbItemClickListener);
        }
      })
    }
    $[end]$

    $[if `process.env.SEARCH === "true"`]$
    const createSearchResult = (result) => {
      const item = document.createElement('a');
      item.classList.add('list-group-item', 'list-group-item-action', 'db-file');
      item.href = "${{`process.env.BASE_PATH ?? ''`}}$" + result.url;
      item.setAttribute('data-file-isdir', result.is_dir);
      // TODO
      // item.setAttribute('data-file-name', result.name);
      // item.setAttribute('data-file-dl', result.dl);
      // item.setAttribute('data-file-size', result.size);
      // item.setAttribute('data-file-mod', result.modified_date);
      item.innerHTML = `
        <div class="row py-2 ">
          <div class="col col-auto pe-0">
            <div class="file-icon-placeholder" filename="${result.name}">
            ${!result.is_dir ? `
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
                <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"></path>
              </svg>` : `<div class="dir-icon-placeholder" dirname="">
                <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-folder-filled" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                  <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                  <path d="M9 3a1 1 0 0 1 .608 .206l.1 .087l2.706 2.707h6.586a3 3 0 0 1 2.995 2.824l.005 .176v8a3 3 0 0 1 -2.824 2.995l-.176 .005h-14a3 3 0 0 1 -2.995 -2.824l-.005 -.176v-11a3 3 0 0 1 2.824 -2.995l.176 -.005h4z" stroke-width="0" fill="currentColor"></path>
                </svg>
              </div>`}
            </div>
          </div>
          <div class="col">
            ${result.name}
          </div>          
        </div>
      `;
      return item;
    }
    const search = async () => {
      const search = document.querySelector('#search').value;
      const api = await fetch(`${{`process.env.BASE_PATH ?? ''`}}$?q=${search}`).then((res) => res.json());
      console.log(api.results)

      document.querySelector('#resultstree').innerHTML = '';
      const infonode = document.createElement('div')
      infonode.innerHTML = `<div class="row py-2"><div class="col col-auto mx-auto">${api.truncated ? "Found more than ${{`process.env.SEARCH_MAX_RESULTS`}}$ results. Narrow down your query." : "Found "+api.total+" results."}</div></div>`
      document.querySelector('#resultstree').appendChild(infonode);
      api.results.forEach((result) => {
        document.querySelector('#resultstree').appendChild(createSearchResult(result));
      });
    };
    const toggleSearch = () => {
      const search = document.querySelector('#search-container');
      search.classList.toggle('d-none');
      const filetree = document.querySelector('#filetree');
      filetree.classList.toggle('d-none');
      const resultstree = document.querySelector('#resultstree');
      resultstree.classList.toggle('d-none');
      if (!search.classList.contains('d-none')) {
        document.querySelector('#search').focus();
      }
    };
    $[end]$

    const sortElements = (key, elems) => elems.sort((a, b) => {
      const aVal = a.getAttribute(`data-file-${key}`);
      const bVal = b.getAttribute(`data-file-${key}`);
      if (key === 'name') {
        return aVal.localeCompare(bVal);
      } else if (key === 'dl') {
        return parseInt(aVal) - parseInt(bVal);
      } else if (key === 'size') {
        return parseInt(aVal) - parseInt(bVal);
      } else if (key === 'mod') {
        return aVal.localeCompare(bVal);
      }
    });
    const sort = (key, reverse) => {
      const items = Array.from(document.querySelectorAll('.db-file'));
      // seperate sort for folders (is_dir) and files
      const folders = sortElements(key, items.filter((item) => item.getAttribute('data-file-isdir') === '1'));
      const files = sortElements(key, items.filter((item) => item.getAttribute('data-file-isdir') === '0'));
      if (reverse) {
        folders.reverse();
        files.reverse();
      }
      // move .. folder top first position
      const parentFolder = folders.find((item) => item.getAttribute('data-file-name') === '..');
      if (parentFolder) {
        folders.splice(folders.indexOf(parentFolder), 1);
        folders.unshift(parentFolder);
      }
      const sorted = [...folders, ...files];
      items.forEach((item) => item.remove());
      sorted.forEach((item) => document.querySelector('#filetree').appendChild(item));
    };
    $[if `process.env.LAYOUT === "popup" || process.env.LAYOUT === "full"`]$
      const setFileinfo = (data) => {
        document.querySelector('#file-popup .modal-title').innerText = data.name;
        document.querySelector("#file-info-url-api").href = data.url + "?info";
        document.querySelector("#file-info-url").href = data.url;
        document.querySelector('#file-popup').classList.add("d-block");
      }
    $[end]$
  </script>
  <script>
    document.querySelectorAll(".filedatetime").forEach(function(element) {
      $[if `process.env.DATE_FORMAT === "utc"`]$
      element.innerHTML = new Date(element.innerHTML.trim()).toISOString().slice(0, 19).replace("T", " ") + " UTC"
      $[if `process.env.DATE_FORMAT === "relative"`]$
      element.innerHTML = getRelativeTimeString(new Date(element.innerHTML.trim())${{`process.env.DATE_FORMAT_RELATIVE_LANG ? ",'"+process.env.DATE_FORMAT_RELATIVE_LANG+"'" : ""`}}$)
      $[else]$
      element.innerHTML = new Date(element.innerHTML.trim()).toLocaleString()
      $[end]$
    })

    // Readme open in new tab fix
    $[if `process.env.OPEN_NEW_TAB === "true"`]$
    document.querySelectorAll("#readme a").forEach((el) => {
      el.setAttribute("target", "_blank");
    });
    $[end]$

    document.querySelectorAll(".stopprop").forEach((el) => {
      el.addEventListener("click", (e) => {
        e.preventDefault();
        // e.stopImmediatePropagation(); //this breaks stuff
      });
    });

    document.querySelectorAll(".drop-toggle").forEach((el) => {
      el.addEventListener("hover", (e) => {
        console.log("hover");
        // close all other dropdowns
        e.preventDefault();
        e.stopImmediatePropagation();
        document.querySelectorAll(".dropdown-menu").forEach((el) => {
          el.classList.remove("show");
        });
        e.target.nextElementSibling.classList.add("show");
      });
    });

    $[if `process.env.BATCH_DOWNLOAD === "true"`]$
    updateMultiselect((localStorage.getItem("multiSelectMode") ?? false) === "true");
    $[end]$

    $[if `process.env.LAYOUT === "popup" || process.env.LAYOUT === "full"`]$
    document.querySelectorAll('.db-file').forEach((item) => {
      // skip folders
      if (item.getAttribute('data-file-isdir') === '1') {
        return;
      }
      item.addEventListener('click', async (e) => {
        e.preventDefault();

        // only do this on reload click button refreshFileinfo()
        // const data = await fetch(item.href + "?info").then((res) => res.json());
        // alert(JSON.stringify(data, null, 2));

        setFileinfo({
          name: item.getAttribute('data-file-name'),
          url: item.href
        });
        document.querySelector('#file-popup').classList.add("d-block");
      })
    });
    document.querySelector('#file-popup').addEventListener('click', (e) => {
      if (e.target === document.querySelector('#file-popup')) {
        document.querySelector('#file-popup').classList.remove("d-block");
      }
    });
    document.querySelector('#file-popup-x').addEventListener('click', (e) => {
      document.querySelector('#file-popup').classList.remove("d-block");
    });
    $[end]$

    $[if `process.env.SEARCH === "true"`]$
    document.querySelector('#search').addEventListener('input', search);
    $[end]$

    document.querySelector('#name').addEventListener('click', (e) => {
      e.preventDefault();
      sessionStorage.setItem("sort:order:name", sessionStorage.getItem("sort:order:name") === "asc" ? "desc" : "asc");
      sort('name', sessionStorage.getItem("sort:order:name") === "desc");
    });

    $[if `process.env.DOWNLOAD_COUNTER === "true"`]$
    document.querySelector('#dl').addEventListener('click', (e) => {
      e.preventDefault();
      sessionStorage.setItem("sort:order:dl", sessionStorage.getItem("sort:order:dl") === "asc" ? "desc" : "asc");
      sort('dl', sessionStorage.getItem("sort:order:dl") === "desc");
    });
    $[end]$

    document.querySelector('#size').addEventListener('click', (e) => {
      e.preventDefault();
      sessionStorage.setItem("sort:order:size", sessionStorage.getItem("sort:order:size") === "asc" ? "desc" : "asc");
      sort('size', sessionStorage.getItem("sort:order:size") === "desc");
    });

    document.querySelector('#mod').addEventListener('click', (e) => {
      e.preventDefault();
      sessionStorage.setItem("sort:order:mod", sessionStorage.getItem("sort:order:mod") === "asc" ? "desc" : "asc");
      sort('mod', sessionStorage.getItem("sort:order:mod") === "desc");
    });
  </script>
  $[if `process.env.ICONS !== "false"`]$
  <script data-turbo-eval="false" src="https://cdn.jsdelivr.net/npm/file-icons-js@1/dist/file-icons.min.js"></script>
  <script>
    var icons = window.FileIcons;
    document.querySelectorAll(".file-icon-placeholder").forEach(function(element) {
      element.classList = ("icon " + icons.getClassWithColor(element.getAttribute("filename"))).replace("null","binary-icon")
      element.innerHTML = ""
    })
  </script>
  $[end]$  
  <!-- TODO: remove bundle -->
  <script data-turbo-eval="false" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>

  $[if `process.env.JS_URL_ONCE !== undefined`]$
  <script data-turbo-eval="false" src="${{`process.env.JS_URL`}}$"></script>
  $[end]$
  $[if `process.env.JS_URL !== undefined`]$
  <script src="${{`process.env.JS_URL`}}$"></script>
  $[end]$
  $[if `process.env.JS !== undefined`]$
  <script>${{`process.env.JS`}}$</script>
  $[end]$
</body>

</html>
