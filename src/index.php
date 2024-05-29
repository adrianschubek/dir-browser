<?php

define('VERSION', '3.0.0');

define('PUBLIC_FOLDER', __DIR__ . '/public');

$[if `process.env.TIMING`]$
$time_start = hrtime(true); 
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
$request_uri = urldecode(parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH));

$url_parts = array_filter(explode(separator: '/', string: $request_uri), fn ($part) => $part !== '');

// get real path and check if accessible (open_basedir)
$local_path = realpath(PUBLIC_FOLDER . $request_uri);

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

// local path exists
if ($path_is_dir) {
  $[if `!process.env.NO_DL_COUNT`]$
  $redis = new Redis();
  $redis->connect('127.0.0.1', 6379);
  $[end]$
  // TODO: refactor use MGET instead of loop GET

  $sorted_files = [];
  $sorted_folders = [];
  foreach (($files = scandir($local_path)) as $file) {
    // always skip current folder '.' or parent folder '..' if current path is root or file should be ignored or .dbmeta.json
    if ($file === '.' || $file === '..' && count($url_parts) === 0 $[if `process.env.IGNORE`]$|| $file !== '..' && fnmatch("${{`process.env.IGNORE ?? ""`}}$", $file)$[end]$ || str_contains($file, ".dbmeta.json")) continue;

    $[if `process.env.IGNORE`]$
    foreach ($url_parts as $int_path) { /* check if parent folders are hidden */
      if (fnmatch("${{`process.env.IGNORE ?? ""`}}$", $int_path)) {
        $path_is_dir = false;
        goto skip; /* Folder should be ignored so skip to 404 */
      }
    }
    $[end]$

    // remove '/var/www/public' from path
    $url = substr($local_path, strlen(PUBLIC_FOLDER)) . '/' . $file;

    $file_size = filesize($local_path . '/' . $file);

    $is_dir = is_dir($local_path . '/' . $file);

    $file_modified_date = gmdate('Y-m-d\TH:i:s\Z', filemtime($local_path . '/' . $file));

    // load metadata if file exists
    $meta_file = realpath($local_path . '/' . $file . '.dbmeta.json');
    if ($meta_file !== false) {
      $meta = json_decode(file_get_contents($meta_file));
      if ($meta !== null && $meta->hidden === true) continue;
    } else {
      // Variables stay alive in php so we need to reset it explicitly
      $meta = null;
    }

    $item = new File();
    $item->name = $file;
    $item->url = $url;
    $item->size = human_filesize($file_size);
    $item->is_dir = $is_dir;
    $item->modified_date = $file_modified_date;
    $item->dl_count =  $[if `!process.env.NO_DL_COUNT`]$!$is_dir ? $redis->get($url) :$[end]$ 0;
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
} elseif (file_exists($local_path)) {
  // local path is file. serve it directly using nginx

  $relative_path = substr($local_path, strlen(PUBLIC_FOLDER));

  $[if `process.env.IGNORE`]$
  foreach ($url_parts as $int_path) { /* check if parent folders are hidden */
    if (fnmatch("${{`process.env.IGNORE ?? ""`}}$", $int_path)) {      
      goto skip; /* File should be ignored so skip to 404 */
    }
  }
  $[end]$

  // skip if file is .dbmeta.json
  if (str_contains($local_path, ".dbmeta.json")) goto skip;

  // check if password proteced
  if (file_exists($local_path . '.dbmeta.json')) {
    $meta = json_decode(file_get_contents($local_path . '.dbmeta.json'));
    if (isset($meta->password)) {
      if (!isset($_REQUEST["key"]) || $_REQUEST["key"] !== $meta->password) { // allows get and post reqeusts
        http_response_code(401);
        define('AUTH_REQUIRED', true);
        goto end;
      }
    }
  }

  $[if `process.env.HASH`]$
  // only allow download if requested hash matches actual hash
  if (isset($_REQUEST["hash"]) || isset($meta) && $meta->hash_required === true) {
    if ($_REQUEST["hash"] !== hash_file('sha256', $local_path)) {
      http_response_code(403);
      die("<b>Access denied.</b> Supplied hash does not match actual file hash.");
    }
  }
  $[end]$

  // increment redis view counter
  $[if `!process.env.NO_DL_COUNT`]$
  $redis = new Redis();
  $redis->connect('127.0.0.1', 6379);
  $redis->incr($relative_path);
  $[end]$

  if(isset($_REQUEST["info"])) {
    $info = [
      "path" => $relative_path,
      "name" => basename($local_path),
      "mime" => mime_content_type($local_path) ?? "application/octet-stream",
      "size" => filesize($local_path),
      "modified" => filemtime($local_path),
      "downloads" => $[if `!process.env.NO_DL_COUNT`]$$redis->get($relative_path)$[else]$0$[end]$,
      "hash" => $[if `process.env.HASH`]$hash_file('sha256', $local_path)$[else]$null$[end]$
    ];
    header("Content-Type: application/json");
    die(json_encode($info));
  }
   
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
  <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.3/dist/cosmo/bootstrap.min.css" rel="stylesheet" data-turbo-eval="false">
  $[end]$
  <style data-turbo-eval="false">
    $[ifeq env:THEME default]$
    [data-bs-theme=dark] {
      --bs-body-bg: #000000;
      --bs-secondary-bg: #000000;
      --bs-tertiary-bg: #000000;
      --bs-tertiary-bg-rgb: 0, 0, 0;
      #filetree > a:hover {
        background-color: #ffffff0d;
      }
    }
    $[end]$
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
    #readme a, #readme a:hover {
      all: revert;
    }
    #filetree > a:hover {
      background-color: var(--bs-tertiary-bg);
    }
    #filetree > a {
      border-bottom: var(--bs-border-width) var(--bs-border-style) var(--bs-border-color) !important;
    }
    #filetree > a:last-child {
      border-bottom: none !important;
    }
  </style>
  $[if `process.env.ICONS !== "false"`]$
  <link data-turbo-eval="false" href="https://cdn.jsdelivr.net/npm/file-icons-js@1/css/style.min.css" rel="stylesheet"></link>
  $[end]$
  <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0/dist/turbo.es2017-umd.min.js"></script>
</head>

<body class="d-flex flex-column min-vh-100">
  <!-- TODO: MOVE darkmode to bottom footer -->
  <nav class="navbar navbar-expand-lg bg-body-tertiary mb-3 shadow-sm d-none">
    <div class="container-fluid">
      <!-- <span class="navbar-brand me-0">
      <a href="${{`process.env.BASE_PATH ?? ''`}}$/">/</a><?php
      // create links e.g. from ["foo","bar","foobar"] to ["/foo", "/foo/bar", "/foo/bar/foobar"]
      $urls = [];
      foreach ($url_parts as $i => $part) {
        $urls[] = end($urls) . '/' . $part;
        // var_dump($i, $part, $urls);
        echo '<a href="${{`process.env.BASE_PATH ?? ''`}}$' . $urls[$i - 1] . '">' . $part . '/</a>';
      }
      ?>
      </span> -->
      <button class="navbar-toggler rounded" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
        <span class="navbar-toggler-icon"></span>
      </button>
      <div class="collapse navbar-collapse" id="navbarSupportedContent">
        <ul class="navbar-nav me-auto mb-2 mb-lg-0">
        </ul>
        <div class="nav-item" data-color-toggler onclick="toggletheme()">
          <a class="btn">
            <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-brightness-half" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
              <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
              <path d="M12 9a3 3 0 0 0 0 6v-6z"></path>
              <path d="M6 6h3.5l2.5 -2.5l2.5 2.5h3.5v3.5l2.5 2.5l-2.5 2.5v3.5h-3.5l-2.5 2.5l-2.5 -2.5h-3.5v-3.5l-2.5 -2.5l2.5 -2.5z">
              </path>
            </svg>
          </a>
        </div>
      </div>
    </div>
  </nav>

  <div class="container py-3">
    <?php if (defined("AUTH_REQUIRED")) { ?>
      <div class="card m-auto" style="max-width: 500px;">
        <div class="card-body">
          <h4 class="alert-heading key-icon">Protected file</h4>
          <p class="mb-2">Please enter the password to access this file.</p>
          <form method="post">
            <input autofocus type="password" class="form-control mb-2" id="key" name="key" required>
            <button type="submit" class="btn btn-primary key-icon form-control">Continue</button>
          </form>
        </div>
      </div>
    <?php } else if (!$path_is_dir) { ?>
      <div class="alert alert-secondary text-center" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-unknown" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
          <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
          <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
          <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"></path>
          <path d="M12 17v.01"></path>
          <path d="M12 14a1.5 1.5 0 1 0 -1.14 -2.474"></path>
        </svg>
        Not Found<br>
        <a class="btn btn-outline-secondary mt-2" href="${{`process.env.BASE_PATH ?? ''`}}$/">Back to Home</a>
      </div>

    <?php } else { ?>
      <div class="rounded container card px-3" id="filetree">
        <div class="row db-row py-2 text-muted">
          <div class="col" id="path">
            <a href="${{`process.env.BASE_PATH ?? ''`}}$/">/</a><?php
            // create links e.g. from ["foo","bar","foobar"] to ["/foo", "/foo/bar", "/foo/bar/foobar"]
            $urls = [];
            foreach ($url_parts as $i => $part) {
              $urls[] = end($urls) . '/' . $part;
              // var_dump($i, $part, $urls);
              echo '<a href="${{`process.env.BASE_PATH ?? ''`}}$' . $urls[$i - 1] . '">' . $part . '/</a>';
            }
            ?>
          </div>
        </div>
        <div class="row db-row py-2 text-muted">
          <div class="col">Name</div>
          $[if `!process.env.NO_DL_COUNT`]$<div class="col col-auto text-end d-none d-md-inline-block">Downloads</div>$[end]$
          <div class="col col-2 text-end d-none d-md-inline-block">Size</div>
          <div class="col col-2 text-end d-none d-md-inline-block">Last Modified</div>
          <!-- <div title="Last modified" class="col col-2 text-end d-sm-none">Mod.</div> -->
        </div>
        <?php
        $now = new DateTime();
        foreach ($sorted as $file) {
          $fileDate = new DateTime($file->modified_date);
          $diff = $now->diff($fileDate)->days;
        ?>
        <a href="${{`process.env.BASE_PATH ?? ''`}}$<?= $file->url ?>" class="row db-row py-2">
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
                if ($file->meta->password !== null) {
            ?>
              <span title="Password protected" class="key-icon"></span>
            <?php
                }
              }
            ?>
            </div>
            <?php if (!$file->is_dir) { ?>
            <div class="col col-auto text-end">
              $[if `!process.env.NO_DL_COUNT`]$
              <span title="Total downloads" class="ms-auto d-none d-md-inline rounded-1 text-end px-1 <?= $file->dl_count === 0 ? "text-body-tertiary" : "" ?>">
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
                <?= $file->size ?>
              </span>
            </div>
            <?php } ?>
            <div class="col col-2">
              <span title="Last modified on <?= $file->modified_date ?>" class="d-none d-md-block text-end filedatetime" ${{`process.env.HIGHLIGHT_UPDATED !== "false" && 'style="font-weight:<?= ($diff > 2 ? "normal !important;": "bold !important;") ?>"'`}}$>
                <?= $file->modified_date ?>
              </span>
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

  $[if `!process.env.NO_README_RENDER`]$
  <?php
    // check if readme exists
    foreach ($sorted_files as $file) {
      if (strtolower($file->name) === "readme.md") {
        $readme = $file;
        break;
      }
    }

    require __DIR__ . "/vendor/autoload.php";
    use League\CommonMark\Environment\Environment;
    use League\CommonMark\Extension\Autolink\AutolinkExtension;
    use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
    use League\CommonMark\Extension\DisallowedRawHtml\DisallowedRawHtmlExtension;
    use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
    use League\CommonMark\Extension\Table\TableExtension;
    use League\CommonMark\Extension\TaskList\TaskListExtension;
    use League\CommonMark\MarkdownConverter;

    if ($readme) {
      // Define your configuration, if needed
      $config = [];

      // Configure the Environment with all the CommonMark parsers/renderers
      $environment = new Environment($config);
      $environment->addExtension(new CommonMarkCoreExtension());

      // Remove any of the lines below if you don't want a particular feature
      $environment->addExtension(new AutolinkExtension());
      ${{`!process.env.ALLOW_RAW_HTML ? "$environment->addExtension(new DisallowedRawHtmlExtension());" : ""`}}$ 
      $environment->addExtension(new StrikethroughExtension());
      $environment->addExtension(new TableExtension());
      $environment->addExtension(new TaskListExtension());
      $converter = new MarkdownConverter($environment);

      $readme_render = $converter->convert(file_get_contents(PUBLIC_FOLDER . $readme->url));
  ?>
  <div class="container pb-3">
    <div class="card rounded p-3" id="readme">
      <?= $readme_render ?>
    </div>
  </div>
  <?php 
  }
  ?>
  $[end]$

  <div class="bg-body-tertiary mt-auto">
    <div class="container py-2 text-center" id="footer">
      <?= $total_items ?> Items | <?= human_filesize($total_size) ?> $[if `process.env.TIMING`]$| <?= (hrtime(true) - $time_start)/1000000 ?> ms $[end]$ $[if `!process.env.HIDE_ATTRIBUTION`]$<br>
    <span style="opacity:0.8"><span style="opacity: 0.8;">Powered by</span>  <a href="https://github.com/adrianschubek/dir-browser" class="text-decoration-none text-primary" target="_blank">dir-browser</a> <?= VERSION ?>$[end]$</span>  
    </div>
  </div>

  <script data-turbo-eval="false" async defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js"></script>
  <!-- Powered by https://github.com/adrianschubek/dir-browser -->
  <script>
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

    document.querySelectorAll(".filedatetime").forEach(function(element) {
      $[if `process.env.DATE_FORMAT === "utc"`]$
      element.innerHTML = new Date(element.innerHTML.trim()).toISOString().slice(0, 19).replace("T", " ") + " UTC"
      $[if `process.env.DATE_FORMAT === "relative"`]$
      element.innerHTML = getRelativeTimeString(new Date(element.innerHTML.trim())${{`process.env.DATE_FORMAT_RELATIVE_LANG ? ",'"+process.env.DATE_FORMAT_RELATIVE_LANG+"'" : ""`}}$)
      $[else]$
      element.innerHTML = new Date(element.innerHTML.trim()).toLocaleString()
      $[end]$
    })
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
  <script data-turbo-eval="false">    
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

    // TODO: sorting
  </script>
</body>

</html>
