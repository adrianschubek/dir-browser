<?php

define('VERSION', '${{`process.env.DIRBROWSER_VERSION`}}$');

define('PUBLIC_FOLDER', __DIR__ . '/public');

$[if `process.env.PASSWORD_HASH !== undefined`]$
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_USER'] !== '${{`process.env.PASSWORD_USER`}}$' || !password_verify($_SERVER['PHP_AUTH_PW'], '${{`process.env.PASSWORD_HASH`}}$')) {
$[if `process.env.PASSWORD_HASH === undefined && process.env.PASSWORD_RAW !== undefined`]$
if (!isset($_SERVER['PHP_AUTH_USER']) || !isset($_SERVER['PHP_AUTH_PW']) || $_SERVER['PHP_AUTH_USER'] !== '${{`process.env.PASSWORD_USER`}}$' || $_SERVER['PHP_AUTH_PW'] !== '${{`process.env.PASSWORD_RAW`}}$') {
$[end]$
$[if `process.env.PASSWORD_RAW !== undefined || process.env.PASSWORD_HASH !== undefined`]$
  header('WWW-Authenticate: Basic realm="dir-browser"');
  header('HTTP/1.0 401 Unauthorized');
  echo "Authentication required. This dir-browser instance is password protected.";
  die;
}
$[end]$

$[if `process.env.TIMING`]$
$time_start = hrtime(true); 
$[end]$

$[if `process.env.README_RENDER === "true"`]$
require_once __DIR__ . "/vendor/autoload.php";
use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\Autolink\AutolinkExtension;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\DisallowedRawHtml\DisallowedRawHtmlExtension;
use League\CommonMark\Extension\Strikethrough\StrikethroughExtension;
use League\CommonMark\Extension\Table\TableExtension;
use League\CommonMark\Extension\TaskList\TaskListExtension;
use League\CommonMark\MarkdownConverter;
$[end]$

$[if `process.env.BATCH_DOWNLOAD === "true"`]$
require_once __DIR__ . "/vendor/autoload.php";
use ZipStream\ZipStream;
use ZipStream\CompressionMethod;
$[end]$

function human_filesize($bytes, $decimals = 2): string
{
  $sz = ' KMGTP';
  $factor = floor((strlen($bytes) - 1) / 3);
  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor] . "B";
}

function normalize_user_path(string $user_path): string
{
  $path = rawurldecode(parse_url($user_path, PHP_URL_PATH) ?? '');
  if ($path === '') return '/';
  if ($path[0] !== '/') $path = '/' . $path;
  return $path;
}

function is_access_config_file_path(string $path): bool
{
  return basename($path) === '.access.json';
}

function request_access_key(): ?string
{
  // Header takes precedence for API clients.
  if (isset($_SERVER['HTTP_X_KEY']) && is_string($_SERVER['HTTP_X_KEY']) && $_SERVER['HTTP_X_KEY'] !== '') {
    return $_SERVER['HTTP_X_KEY'];
  }

  // UI unlock form submits via POST.
  if (isset($_POST['key']) && is_string($_POST['key']) && $_POST['key'] !== '') {
    return $_POST['key'];
  }

  // Cookie-based auth for browser sessions.
  if (isset($_COOKIE['dir_browser_key']) && is_string($_COOKIE['dir_browser_key']) && $_COOKIE['dir_browser_key'] !== '') {
    return $_COOKIE['dir_browser_key'];
  }

  // Legacy support (not promoted): allow ?key=... but we will redirect to a clean URL.
  if (isset($_GET['key']) && is_string($_GET['key']) && $_GET['key'] !== '') {
    return $_GET['key'];
  }

  return null;
}

function set_auth_cookie(string $key): void
{
  $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  $path = '${{`process.env.BASE_PATH ?? ''`}}$';
  if ($path === '') $path = '/';
  // Cookie is httpOnly to avoid leaking via JS.
  setcookie('dir_browser_key', $key, [
    'expires' => time() + ${{`process.env.AUTH_COOKIE_LIFETIME`}}$,
    'path' => $path,
    'secure' => $secure,
    'httponly' => ${{`process.env.AUTH_COOKIE_HTTPONLY`}}$,
    'samesite' => 'Lax',
  ]);
}

function delete_auth_cookie(): void
{
  $secure = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off');
  $path = '${{`process.env.BASE_PATH ?? ''`}}$';
  if ($path === '') $path = '/';
  // Match cookie attributes used in set_auth_cookie to ensure deletion.
  setcookie('dir_browser_key', '', [
    'expires' => time() - 3600,
    'path' => $path,
    'secure' => $secure,
    'httponly' => ${{`process.env.AUTH_COOKIE_HTTPONLY`}}$,
    'samesite' => 'Lax',
  ]);
  unset($_COOKIE['dir_browser_key']);
}

function redirect_without_key_param(): void
{
  $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
  $query = [];
  parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $query);
  unset($query['key']);
  $qs = http_build_query($query);
  header('Location: ' . $path . ($qs !== '' ? ('?' . $qs) : ''));
  http_response_code(303);
  die();
}

// Simple route: /some/path?logout (clears cookie-based auth).
if (isset($_GET['logout'])) {
  delete_auth_cookie();
  $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
  $query = [];
  parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $query);
  unset($query['logout']);
  $qs = http_build_query($query);
  header('Location: ' . $path . ($qs !== '' ? ('?' . $qs) : ''));
  http_response_code(303);
  die();
}

/**
 * Read a .access.json file from a directory.
 * Returns an associative array with only keys that were explicitly set.
 * Supported keys: password_hash (string), password_raw (string), hidden (bool), inherit (bool)
 */
function read_access_config(string $dir): ?array
{
  static $cache = [];
  if (isset($cache[$dir])) return $cache[$dir] ?: null;
  $path = rtrim($dir, '/') . '/.access.json';
  if (!is_file($path)) {
    $cache[$dir] = false;
    return null;
  }

  $raw = @file_get_contents($path);
  if ($raw === false) {
    $cache[$dir] = false;
    return null;
  }

  $json = json_decode($raw, true);
  if (!is_array($json)) {
    $cache[$dir] = false;
    return null;
  }

  $cfg = [];
  if (array_key_exists('password_hash', $json) && is_string($json['password_hash']) && $json['password_hash'] !== '') {
    $cfg['password_hash'] = $json['password_hash'];
  }
  if (array_key_exists('password_raw', $json) && is_string($json['password_raw']) && $json['password_raw'] !== '') {
    $cfg['password_raw'] = $json['password_raw'];
  }
  if (array_key_exists('hidden', $json)) {
    $cfg['hidden'] = (bool) $json['hidden'];
  }
  if (array_key_exists('inherit', $json)) {
    $cfg['inherit'] = (bool) $json['inherit'];
  }

  $cache[$dir] = $cfg;
  return $cfg;
}

/**
 * Compute effective access config for a directory by evaluating .access.json
 * in the directory and all its parents up to PUBLIC_FOLDER.
 *
 * Rules:
 * - Current directory config always applies.
 * - Parent configs apply only if their inherit=true (default true when unspecified).
 * - Child configs override parent configs.
 */
function effective_access_for_dir(string $dir): array
{
  static $effectiveCache = [];
  if (isset($effectiveCache[$dir])) return $effectiveCache[$dir];

  $chain = [];
  $cursor = $dir;
  while (true) {
    $cfg = read_access_config($cursor);
    if ($cfg !== null) {
      $chain[] = ['dir' => $cursor, 'cfg' => $cfg];
    }
    if ($cursor === PUBLIC_FOLDER) break;
    $parent = dirname($cursor);
    if ($parent === $cursor || !str_starts_with($parent, PUBLIC_FOLDER)) break;
    $cursor = $parent;
  }

  $chain = array_reverse($chain);

  $effective = [
    'hidden' => false,
    'requires_password' => false,
  ];

  foreach ($chain as $entry) {
    $cfgDir = $entry['dir'];
    $cfg = $entry['cfg'];

    $applies = ($cfgDir === $dir) || (($cfg['inherit'] ?? true) === true);
    if (!$applies) continue;

    if (array_key_exists('hidden', $cfg)) {
      $effective['hidden'] = (bool) $cfg['hidden'];
    }

    // Password: child overrides parent.
    if (array_key_exists('password_hash', $cfg)) {
      $effective['password_hash'] = $cfg['password_hash'];
      unset($effective['password_raw']);
    }
    if (!array_key_exists('password_hash', $cfg) && array_key_exists('password_raw', $cfg)) {
      $effective['password_raw'] = $cfg['password_raw'];
      unset($effective['password_hash']);
    }
  }

  $effective['requires_password'] = isset($effective['password_hash']) || isset($effective['password_raw']);
  $effectiveCache[$dir] = $effective;
  return $effective;
}

function access_key_authorized(array $effective, ?string $key): bool
{
  if (!($effective['requires_password'] ?? false)) return true;
  if ($key === null || $key === '') return false;
  if (isset($effective['password_hash'])) {
    return password_verify($key, $effective['password_hash']);
  }
  if (isset($effective['password_raw'])) {
    return hash_equals((string) $effective['password_raw'], (string) $key);
  }
  return false;
}

/**
 * Evaluate access status for a local filesystem path (file or directory).
 * - hidden: if true, resource should behave as not found.
 * - requires_password: if true and not authorized, resource should require unlock.
 */
function access_status_for_local_path(string $local_path, bool $includeProtected = false): array
{
  $dir = is_dir($local_path) ? $local_path : dirname($local_path);
  $effective = effective_access_for_dir($dir);
  $key = request_access_key();

  $authorized = access_key_authorized($effective, $key);
  if ($includeProtected) {
    // For search indexing: allow returning protected URLs, but still respect hidden.
    $authorized = true;
  }

  return [
    'hidden' => (bool) ($effective['hidden'] ?? false),
    'requires_password' => (bool) ($effective['requires_password'] ?? false),
    'authorized' => $authorized,
  ];
}

function numsize($size, $round = 2)
{
  if ($size === 0) return '0';
  $unit = ['', 'K', 'M', 'B', 'T'];
  return round($size / pow(1000, ($i = floor(log($size, 1000)))), $round) . $unit[$i];
}

function safe_utf8(string $input): string
{
  // Ensure JSON output is valid UTF-8.
  // iconv is commonly available; if it fails, fall back to stripping invalid bytes.
  $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $input);
  if ($converted !== false) return $converted;
  return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $input) ?? '';
}

function hash_max_file_size_bytes(): ?int
{
  $raw = getenv('HASH_MAX_FILE_SIZE_MB');
  if ($raw === false || $raw === '') return null;
  if (!is_numeric($raw)) return null;
  $mb = floatval($raw);
  if ($mb <= 0) return null;
  $bytes = (int) floor($mb * 1024 * 1024);
  return $bytes > 0 ? $bytes : null;
}

function hashing_allowed_for_file(string $path): bool
{
  $maxBytes = hash_max_file_size_bytes();
  if ($maxBytes === null) return true;
  $size = @filesize($path);
  if ($size === false) return false;
  return $size <= $maxBytes;
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
  public int $dl_count = 0;
  public ?object $meta;
  public bool $auth_required = false;
  public bool $auth_locked = false;

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
  $user_path = normalize_user_path($user_path);
  if (is_access_config_file_path($user_path)) return false;
  $path = realpath(PUBLIC_FOLDER . $user_path);
  if ($path === false || !str_starts_with($path, PUBLIC_FOLDER) || hidden($path)) return false;
  // Folder-level access control via .access.json
  $access = access_status_for_local_path($path, $includeProtected);
  if ($access['hidden']) return false;
  if (!$includeProtected && $access['requires_password'] && !$access['authorized']) return false;
  $[if `process.env.METADATA === "true"`]$
  if (str_contains($path, ".dbmeta.")) return false;
  $meta_file = realpath($path . '.dbmeta.json');
  if ($meta_file !== false) {
    $meta = json_decode(file_get_contents($meta_file));
    if (!isset($meta)) return true;
    // hidden check
    if (isset($meta->hidden) && $meta->hidden === true) return false;
  }
  $[end]$
  return $path;
}

// for batch download
function getDeepUrlsFromArray(array $input_urls): array {
  $urls = [];
  foreach ($input_urls as $url) {
    $url = normalize_user_path((string) $url);
    if (($path = available($url)) !== false) {
      if (is_dir($path)) {
        // scan this folder. exclude special folders
        $deep_files = array_diff(scandir($path), ['.', '..']);
        // scandir returns all files with full filesystem path so strip PUBLIC_FOLDER
        $deep_urls = array_map(fn ($file) => substr($path . '/' . $file, strlen(PUBLIC_FOLDER)), $deep_files);
        // recursion
        $urls = array_merge($urls, getDeepUrlsFromArray($deep_urls));
      } else {
        // Always return canonical, decoded URLs (avoids %20 false negatives).
        $urls[] = substr($path, strlen(PUBLIC_FOLDER));
      }
    }
  }
  return $urls;
}

/**
 * Regex Search for files and folders in root_folder
 * @return array<File>
 */
function globalsearch(string $query, string $root_folder, string $engine): array {
  if ($engine === "s" || $engine === "r") {
    $rdit = new RecursiveDirectoryIterator($root_folder, RecursiveDirectoryIterator::SKIP_DOTS);
    $rit = new RecursiveIteratorIterator($rdit);
    $rit->setMaxDepth(${{`process.env.SEARCH_MAX_DEPTH`}}$);
  }
  if ($engine === "r") {
    $found = new RegexIterator($rit, "/$query/", RecursiveRegexIterator::MATCH);
  }
  if ($engine === "s") {
    $found = new CallbackFilterIterator($rit, function ($current) use ($query) {
      return str_contains(mb_strtolower($current->getFilename()), mb_strtolower($query));
    });
  }
  if ($engine === "g") {
    $found = new GlobIterator($root_folder . "/" . $query, FilesystemIterator::SKIP_DOTS);
  }
  $found = array_keys(iterator_to_array($found));
  $search_results = [];
  $found_counter = 0;
  foreach ($found as $path) {
    if ($found_counter >= ${{`process.env.SEARCH_MAX_RESULTS`}}$) break;
    if (($path = available(substr($path, strlen(PUBLIC_FOLDER)), true)) !== false) {
      // only paths are returned due to performance reasons
      $is_dir = is_dir($path);
      $auth_locked = false;
      $auth_required = false;
      if ($is_dir) {
        $a = access_status_for_local_path($path);
        $auth_required = (bool) $a['requires_password'];
        $auth_locked = $auth_required && !$a['authorized'];
      }
      $search_results[] = [
        "url" => substr($path, strlen(PUBLIC_FOLDER)),
        // strip base path from url
        "name" => substr($path, strlen($root_folder) + 1 /* slash */),
        "is_dir" => $is_dir,
        "auth_required" => $auth_required,
        "auth_locked" => $auth_locked,
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

$[if `process.env.SEARCH === "true"`]$
// global search api
if (isset($_REQUEST["q"]) && isset($_REQUEST["e"]) && $path_is_dir) {
  $access = access_status_for_local_path($local_path);
  if ($access['hidden']) {
    http_response_code(404);
    die("Not found");
  }
  if ($access['requires_password'] && !$access['authorized']) {
    http_response_code(401);
    die("Authentication required");
  }
  $search = $_REQUEST["q"];
  $engine = $_REQUEST["e"];
  if ($search === "") {
    http_response_code(400);
    die("Empty search query");
  }
  if (array_search($engine, explode(',', "${{`process.env.SEARCH_ENGINE`}}$")) === false) {
    http_response_code(400);
    die("Invalid search engine");
  }

  // start search from current folder
  $search_results = globalsearch($search, $local_path, $engine);
  header("Content-Type: application/json");
  die(json_encode($search_results));
}
$[end]$

$[if `process.env.BATCH_DOWNLOAD === "true"`]$
function downloadBatch(array $urls) {
  $all_urls = getDeepUrlsFromArray($urls);

  // Pre-validate everything before sending any output/headers.
  $total_size = 0;
  $max_file_size = 0;
  $files_to_zip = [];
  $validation_error = null;
  foreach ($all_urls as $user_url) {
    // Paths are posted from the browser and may contain URL-encoded characters
    // (e.g. spaces as %20). Normalize by decoding before validating.
    $decoded_user_url = rawurldecode((string) $user_url);
    $full_path = available($decoded_user_url);
    if ($full_path === false || !is_file($full_path)) {
      $validation_error = "Invalid file in batch: $user_url";
      break;
    }

    // Re-derive canonical URL from realpath() to avoid zip-slip names like a/../b.
    $canonical_url = substr($full_path, strlen(PUBLIC_FOLDER));
    $fs = filesize($full_path);
    if ($fs === false) {
      $validation_error = "Cannot read filesize for $canonical_url";
      break;
    }
    if ($fs > 1024 * 1024 * ${{`process.env.BATCH_MAX_FILE_SIZE`}}$) {
      $validation_error = "File $canonical_url exceeds ${{`process.env.BATCH_MAX_FILE_SIZE`}}$ MB limit";
      break;
    }
    $total_size += $fs;
    if ($fs > $max_file_size) {
      $max_file_size = $fs;
    }
    if ($total_size > 1024 * 1024 * ${{`process.env.BATCH_MAX_TOTAL_SIZE`}}$) {
      $validation_error = "Total size of files exceeds ${{`process.env.BATCH_MAX_TOTAL_SIZE`}}$ MB limit";
      break;
    }

    // Remove leading "/" bc some ZIP clients on Windows can be picky.
    $archive_name = ltrim($canonical_url, '/');
    if ($archive_name === '') {
      $validation_error = 'Invalid empty archive name';
      break;
    }

    $files_to_zip[] = [
      'archive_name' => $archive_name,
      'full_path' => $full_path,
      'size' => $fs,
      'mtime' => @filemtime($full_path) ?: null,
    ];
  }

  if ($validation_error !== null) {
    http_response_code(400);
    header('Content-Type: text/plain; charset=utf-8');
    echo "Batch download error: " . $validation_error;
    die();
  }

  $[end]$
  $[if `process.env.BATCH_DOWNLOAD === "true" && process.env.DOWNLOAD_COUNTER === "true"`]$
  $redis = new Redis();
  $redis->connect('127.0.0.1', 6379);
  $dl_counters = $redis->mget($all_urls);
  $new_dl_counters = [];
  for ($i = 0; $i < count($all_urls); $i++) $new_dl_counters[$all_urls[$i]] = ($dl_counters[$i] ?? 0) + 1;
  $redis->mset($new_dl_counters);
  $[end]$
  $[if `process.env.BATCH_DOWNLOAD === "true"`]$
  try {
    $streaming_started = false;

    // Disable buffering/compression where possible to allow true streaming.
    @ini_set('zlib.output_compression', 'Off');
    @ini_set('implicit_flush', '1');
    @ini_set('output_buffering', 'Off');
    @ini_set('display_errors', '0');
    @ini_set('html_errors', '0');
    @set_time_limit(0);
    @ignore_user_abort(true);
    while (ob_get_level() > 0) {
      @ob_end_clean();
    }
    @ob_implicit_flush(true);

    // Tell nginx to not buffer the (potentially huge) ZIP response.
    header('X-Accel-Buffering: no');
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    header('Expires: 0');
    // Ensure the response is not compressed by intermediaries.
    header('Content-Encoding: identity');

    $compression = CompressionMethod::DEFLATE;
    $algo = strtoupper((string) '${{`process.env.BATCH_ZIP_COMPRESS_ALGO`}}$');
    if ($algo === 'STORE' || $algo === 'STORED') {
      $compression = CompressionMethod::STORE;
    }

    $zip = new ZipStream(
      outputName: bin2hex(random_bytes(8)) . '.zip',
      sendHttpHeaders: true,
      contentType: 'application/zip',
      defaultCompressionMethod: $compression,
      defaultEnableZeroHeader: true,
      enableZip64: true,
      flushOutput: true,
    );

    // At this point, ZipStream may start emitting bytes as files are added.
    $streaming_started = true;

    foreach ($files_to_zip as $f) {
      $lastMod = null;
      if ($f['mtime'] !== null) {
        $lastMod = (new DateTimeImmutable())->setTimestamp((int) $f['mtime']);
      }
      $zip->addFileFromPath(
        fileName: $f['archive_name'],
        path: $f['full_path'],
        lastModificationDateTime: $lastMod,
        exactSize: (int) $f['size'],
        enableZeroHeader: false,
      );
    }

    $zip->finish();
    die();
  } catch (\Throwable $th) {
    // If we've already started streaming ZIP bytes, do NOT write any additional
    // output (it corrupts the archive). Best effort is to terminate.
    if (!$streaming_started && !headers_sent()) {
      http_response_code(500);
      header('Content-Type: text/plain; charset=utf-8');
      echo "Batch download error: " . $th->getMessage();
    }
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

  // Folder access control (.access.json)
  $access = access_status_for_local_path($local_path);
  if ($access['hidden']) {
    $path_is_dir = false;
    goto skip;
  }
  if ($access['requires_password']) {
    if ($access['authorized']) {
      // Persist in cookie if user supplied a key via POST or legacy ?key.
      $k = request_access_key();
      if ($k !== null && ($k !== ($_COOKIE['dir_browser_key'] ?? null)) && (isset($_POST['key']) || isset($_GET['key']))) {
        set_auth_cookie($k);
      }
      // PRG pattern: after successful POST (or legacy ?key), redirect to clean URL without key.
      if (isset($_POST['key']) || isset($_GET['key'])) {
        redirect_without_key_param();
      }
    } else {
      http_response_code(401);
      define('AUTH_REQUIRED', true);
      define('AUTH_RESOURCE', 'folder');
      if (isset($_POST['key']) && is_string($_POST['key']) && $_POST['key'] !== '') {
        define('AUTH_ERROR', true);
      }
      goto end;
    }
  }

  $sorted_files = [];
  $sorted_folders = [];
  
  // Use FilesystemIterator for better performance
  $iterator = new FilesystemIterator(
    $local_path,
    FilesystemIterator::KEY_AS_PATHNAME | FilesystemIterator::CURRENT_AS_FILEINFO | FilesystemIterator::SKIP_DOTS
  );

  // Add the parent directory ".." manually if not in the root
  if (count($url_parts) > 0) {
    $parent_path = dirname($local_path . '/.'); // a safe way to get parent
    $parent_file_info = new SplFileInfo($parent_path);
    $item = new File();
    $item->name = '..';
    $item->url = "/" . implode(separator: '/', array: array_slice($url_parts, 0, -1)); // remove last part
    // remove last slash if exists
    if (str_ends_with($item->url, '/')) {
      $item->url = substr($item->url, 0, -1); // temp fix
    }
    $item->size = 0; // Or $parent_file_info->getSize();
    $item->is_dir = true;
    $item->modified_date = gmdate('Y-m-d\TH:i:s\Z', $parent_file_info->getMTime());
    $item->meta = null;
    $sorted_folders[] = $item;
  }

  foreach ($iterator as $path => $fileinfo) {
    /* @var SplFileInfo $fileinfo */
    $filename = $fileinfo->getFilename();
    $url = substr($path, strlen(PUBLIC_FOLDER));

    // Skip hidden files or metadata files
    if ($filename === '.access.json' || hidden($url) $[if `process.env.METADATA === "true"`]$ || str_contains($filename, ".dbmeta.")$[end]$) {
      continue;
    }

    $is_dir = $fileinfo->isDir();
    $meta = null; // Reset meta

    // Folder access state for UI
    $auth_required = false;
    $auth_locked = false;
    if ($is_dir) {
      $childAccess = access_status_for_local_path($path);
      if ($childAccess['hidden']) {
        continue;
      }
      $auth_required = (bool) $childAccess['requires_password'];
      $auth_locked = $auth_required && !$childAccess['authorized'];
    }

    $[if `process.env.METADATA === "true"`]$
    $meta_file_path = $path . '.dbmeta.json';
    if (file_exists($meta_file_path)) {
      $meta_content = file_get_contents($meta_file_path);
      if ($meta_content) {
        $meta = json_decode($meta_content);
        if (isset($meta->description)) { // escape meta->description 
          $meta->description = htmlspecialchars($meta->description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
        }
        if ($meta?->hidden === true) {
          continue;
        }
      }
    }
    $[end]$

    $item = new File();
    $item->name = $filename;
    $item->url = $url;
    $item->size = $is_dir ? 0 : $fileinfo->getSize(); // Dirs don't have a relevant size here
    $item->is_dir = $is_dir;
    $item->modified_date = gmdate('Y-m-d\TH:i:s\Z', $fileinfo->getMTime());
    $item->meta = $meta;
    $item->auth_required = $auth_required;
    $item->auth_locked = $auth_locked;

    if ($is_dir) {
      $sorted_folders[] = $item;
    } else {
      $sorted_files[] = $item;
      $total_size += $item->size;
    }
  }
  $total_items = count($sorted_folders) + count($sorted_files) - (count($url_parts) > 0 ? 1 : 0); // Exclude '..' from count

  // fast! mget for folders and files
  $[if `process.env.DOWNLOAD_COUNTER === "true"`]$
  $dl_counters = $redis->mget(array_map(fn ($file) => $file->url, array_merge($sorted_folders, $sorted_files)));
  $folders_len_tmp = count($sorted_folders);
  $files_len_tmp = count($sorted_files);
  for ($i = 0; $i < $folders_len_tmp; $i++) $sorted_folders[$i]->dl_count = $dl_counters[$i];
  for ($i = 0; $i < $files_len_tmp; $i++) $sorted_files[$i]->dl_count = $dl_counters[$i + $folders_len_tmp];
  $[end]$

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
    $[end]$
    $[if `process.env.API === "true" && process.env.DOWNLOAD_COUNTER === "true"`]$
    // Get all counters in one call
    $urls = array_map(fn($file) => $file->url, $sorted);
    $all_counters = $redis->mget($urls);
    $counters_map = array_combine($urls, $all_counters);
    $[end]$
    $[if `process.env.API === "true"`]$

    foreach ($sorted as $file) {
      if ($file->name === "..") continue; // skip parent folder
      $info[] = [
        "url" => $file->url,
        "name" => $file->name,
        "type" => $file->is_dir ? "dir" : "file",
        "size" => intval($file->size),
        "modified" => $file->modified_date,
        "downloads" => ${{`process.env.DOWNLOAD_COUNTER === "true" ? "intval($counters_map[$file->url] ?? 0)" : "0"`}}$
      ];
    }
    header("Content-Type: application/json");
    die(json_encode($info, JSON_UNESCAPED_SLASHES));
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

  // Pagination
  $current_page = max(1, intval($_GET["p"] ?? 1));
  $max_pages = ceil(count($sorted) / ${{`process.env.PAGINATION_PER_PAGE`}}$);
  $current_page = min($current_page, $max_pages);
  $page_start_offset = ($current_page - 1) * ${{`process.env.PAGINATION_PER_PAGE`}}$;
  $sorted = array_slice($sorted, $page_start_offset, ${{`process.env.PAGINATION_PER_PAGE`}}$);
  $actual_length = count($sorted) - 1; // removes .. from count

  $pages = [$current_page];
  // add first 2 .. and last 2 pages. add ".." in between
  for ($i = 1; $i <= 2; $i++) {
    if ($current_page - $i > 1) array_unshift($pages, $current_page - $i);
    if ($current_page + $i < $max_pages) array_push($pages, $current_page + $i);
  }
  // add first and last page
  if ($current_page - 3 > 1) array_unshift($pages, "..");
  if ($current_page + 3 < $max_pages) array_push($pages, "..");
  if ($current_page != 1) array_unshift($pages, 1);
  if ($current_page !== $max_pages) array_push($pages, $max_pages);

} elseif (file_exists($local_path)) {
  // local path is file. serve it directly using nginx

  $relative_path = substr($local_path, strlen(PUBLIC_FOLDER));

  if (hidden($relative_path)) {      
    goto skip; /* File should be ignored so skip to 404 */
  }

  if (is_access_config_file_path($relative_path)) {
    goto skip;
  }

  // Folder access control (.access.json)
  $access = access_status_for_local_path($local_path);
  if ($access['hidden']) {
    goto skip;
  }
  if ($access['requires_password']) {
    if ($access['authorized']) {
      $k = request_access_key();
      if ($k !== null && ($k !== ($_COOKIE['dir_browser_key'] ?? null)) && (isset($_POST['key']) || isset($_GET['key']))) {
        set_auth_cookie($k);
      }
      if (isset($_POST['key']) || isset($_GET['key'])) {
        redirect_without_key_param();
      }
    } else {
      http_response_code(401);
      define('AUTH_REQUIRED', true);
      define('AUTH_RESOURCE', 'file');
      if (isset($_POST['key']) && is_string($_POST['key']) && $_POST['key'] !== '') {
        define('AUTH_ERROR', true);
      }
      goto end;
    }
  }

  $[if `process.env.METADATA === "true"`]$
  // skip if file is .dbmeta.
  if (str_contains($local_path, ".dbmeta.")) goto skip;
  $[end]$

  $[if `process.env.HASH`]$
  // only allow download if requested hash matches actual hash
  if (${{`process.env.HASH_REQUIRED === "true" ? "true ||" : ""`}}$ isset($_REQUEST["hash"]) || isset($meta) && $meta->hash_required === true) {
    if (!hashing_allowed_for_file($local_path)) {
      http_response_code(413);
      $limitMb = getenv('HASH_MAX_FILE_SIZE_MB');
      die("<b>Hashing disabled.</b> File exceeds HASH_MAX_FILE_SIZE_MB (" . htmlspecialchars((string) $limitMb) . " MB).");
    }
    if (!isset($_REQUEST["hash"])) {
      http_response_code(403);
      die("<b>Access denied.</b> Hash is required for this file.");
    }
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
    $hash_value = null;
    $[end]$
    $[if `process.env.API === "true" && process.env.HASH === "true"`]$
    if (hashing_allowed_for_file($local_path)) {
      $hash_value = hash_file('${{`process.env.HASH_ALGO`}}$', $local_path);
    }
    $[end]$
    $[if `process.env.API === "true"`]$
    $info = [
      "url" => $relative_path, // FIXME: use host domain! abc.de/foobar
      "name" => basename($local_path),
      "mime" => mime_content_type($local_path) ?? "application/octet-stream",
      "size" => filesize($local_path),
      "modified" => filemtime($local_path),
      "downloads" => ${{`process.env.DOWNLOAD_COUNTER === "true" ? "intval($redis->get($relative_path))" : "0"`}}$,
      "hash_${{`process.env.HASH_ALGO`}}$" => $hash_value
    ];
    header("Content-Type: application/json");
    die(json_encode($info, JSON_UNESCAPED_SLASHES));
  }
  $[end]$

  // Popup preview endpoint (kept separate from API=true)
  // Returns a small preview + metadata without incrementing download counter.
  if (isset($_REQUEST["preview"])) {
    $mime = mime_content_type($local_path) ?? "application/octet-stream";
    $ext = mb_strtolower(pathinfo($local_path, PATHINFO_EXTENSION));

    $kind = "none";
    if (str_starts_with($mime, "image/")) {
      $kind = "image";
    } elseif (str_starts_with($mime, "video/")) {
      $kind = "video";
    } elseif (str_starts_with($mime, "audio/") || in_array($ext, ["mp3", "m4a", "aac", "wav", "ogg", "oga", "opus", "flac"])) {
      $kind = "audio";
    } elseif ($mime === "application/json" || $ext === "json") {
      $kind = "json";
    } elseif ($mime === "text/csv" || $ext === "csv") {
      $kind = "csv";
    } elseif (str_starts_with($mime, "text/") || in_array($ext, ["txt", "log", "md", "yaml", "yml", "ini", "conf", "xml", "html", "css", "js", "ts", "php"])) {
      $kind = "text";
    }

    $preview = [
      "kind" => $kind,
      "mime" => $mime,
      "truncated" => false,
      "text" => null,
    ];

    // For media we don't inline bytes; client will request ?raw=1.
    if ($kind === "text" || $kind === "json" || $kind === "csv") {
      $max_bytes = 128 * 1024;
      $raw = @file_get_contents($local_path, false, null, 0, $max_bytes + 1);
      if ($raw === false) {
        $preview["kind"] = "none";
      } else {
        $preview["truncated"] = strlen($raw) > $max_bytes;
        if ($preview["truncated"]) {
          $raw = substr($raw, 0, $max_bytes);
        }

        // Make sure it's safe for JSON encoding.
        $raw = safe_utf8($raw);

        if ($kind === "json") {
          $decoded = json_decode($raw);
          if ($decoded !== null || trim($raw) === "null") {
            $pretty = json_encode($decoded, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
            $preview["text"] = $pretty === false ? $raw : $pretty;
          } else {
            // Invalid JSON; fall back to raw text preview.
            $preview["kind"] = "text";
            $preview["text"] = $raw;
          }
        } else {
          $preview["text"] = $raw;
        }
      }
    }

    $size = filesize($local_path);
    $modified_iso = gmdate('Y-m-d\\TH:i:s\\Z', filemtime($local_path));

    $payload = [
      "url" => $relative_path,
      "name" => basename($local_path),
      "mime" => $mime,
      "size" => $size,
      "size_human" => human_filesize($size),
      "modified" => $modified_iso,
      "downloads" => ${{`process.env.DOWNLOAD_COUNTER === "true" ? "intval($redis->get($relative_path))" : "0"`}}$,
      "preview" => $preview,
    ];
    header("Content-Type: application/json");
    die(json_encode($payload));
  }

  // Raw streaming for popup media previews without increasing download counter.
  if (isset($_REQUEST["raw"])) {
    header("Content-Type: ");
    header("X-Accel-Redirect: /__internal_public__" . $relative_path);
    die();
  }

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
  <title>${{`process.env.TITLE`}}$ - <?= '/' . implode(separator: '/', array: $url_parts) ?></title>
  $[ifeq env:THEME cerulean]$
  <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.8/dist/cerulean/bootstrap.min.css" rel="stylesheet" data-turbo-eval="false">
  $[ifeq env:THEME materia]$
  <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.8/dist/materia/bootstrap.min.css" rel="stylesheet" data-turbo-eval="false">
  $[ifeq env:THEME quartz]$
  <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.8/dist/quartz/bootstrap.min.css" rel="stylesheet" data-turbo-eval="false">
  $[ifeq env:THEME sandstone]$
  <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.8/dist/sandstone/bootstrap.min.css" rel="stylesheet" data-turbo-eval="false">
  $[ifeq env:THEME sketchy]$
  <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.8/dist/sketchy/bootstrap.min.css" rel="stylesheet" data-turbo-eval="false">
  $[ifeq env:THEME united]$
  <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.8/dist/united/bootstrap.min.css" rel="stylesheet" data-turbo-eval="false">
  $[ifeq env:THEME yeti]$
  <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.8/dist/yeti/bootstrap.min.css" rel="stylesheet" data-turbo-eval="false">
  $[ifeq env:THEME litera]$
  <link href="https://cdn.jsdelivr.net/npm/bootswatch@5.3.8/dist/litera/bootstrap.min.css" rel="stylesheet" data-turbo-eval="false">
  $[else]$
  <link href="${{`process.env.THEME_URL !== undefined ? process.env.THEME_URL : "https://cdn.jsdelivr.net/npm/bootswatch@5.3.8/dist/cosmo/bootstrap.min.css"`}}$" rel="stylesheet" data-turbo-eval="false">
  $[end]$
  $[ifeq env:README_RENDER true]$
  <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/adrianschubek/dir-browser@3.x/assets/readme/gh.css" data-turbo-eval="false"/>
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

    .pagination {      
      border-radius: var(--bs-border-radius-lg) !important;
    }
    /* .pagination > li > a {
      color: var(--bs-secondary-color) !important;
    } */
    .pagination > li > a > svg {
      margin-bottom: 3px;
    }
    .pagination > li:first-child > a {
      border-top-left-radius: var(--bs-border-radius-lg) !important;
      border-bottom-left-radius: var(--bs-border-radius-lg) !important;
    } 
    .pagination > li:last-child > a {
      border-top-right-radius: var(--bs-border-radius-lg) !important;
      border-bottom-right-radius: var(--bs-border-radius-lg) !important;
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
      <div class="card rounded  p-3 markdown-body-light markdown-body-dark" id="readme">
        <?= $readme_render ?>
      </div>
    </div>
    <?php 
    }
    ?>
  $[end]$
  <div class="container py-3">    
    <?php if (defined("AUTH_REQUIRED")) { ?>
      <div class="card rounded  m-auto" style="max-width: 500px;">
        <div class="card-body">
          <h4 class="alert-heading key-icon"><?= (defined('AUTH_RESOURCE') && AUTH_RESOURCE === 'folder') ? 'Protected folder' : 'Protected file' ?></h4>
          <p class="mb-2">Please enter the password to access this content.</p>
          <?php if (defined('AUTH_ERROR')) { ?>
            <div class="alert alert-danger py-2" role="alert">Incorrect password.</div>
          <?php } ?>
          <form method="post" data-turbo="false">
            <input autofocus type="password" class="form-control mb-2 rounded" id="key" name="key" required>
            <button type="submit" class="btn rounded btn-primary key-icon form-control">Unlock</button>
          </form>
        </div>
      </div>
    <?php } else if (!$path_is_dir) { ?>
      <div class="card rounded  m-auto" style="max-width: 500px;">
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
      <div class="rounded container position-sticky card  px-3" style="top:0; z-index: 5;border-bottom-left-radius: 0 !important;border-bottom-right-radius: 0 !important;">
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
            <?php
              $show_logout = isset($access) && is_array($access) && (($access['requires_password'] ?? false) === true);
              $logout_href = '';
              if ($show_logout) {
                $logout_path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
                $logout_query = [];
                parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $logout_query);
                $logout_query['logout'] = '1';
                $logout_qs = http_build_query($logout_query);
                $logout_href = $logout_path . ($logout_qs !== '' ? ('?' . $logout_qs) : '');
              }
            ?>
            <?php if ($show_logout) { ?>
              <a href="<?= htmlspecialchars($logout_href) ?>" class="btn rounded btn-sm text-muted" id="icon" title="Logout" data-turbo="false"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#C62828" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="icon icon-tabler icons-tabler-outline icon-tabler-logout"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M14 8v-2a2 2 0 0 0 -2 -2h-7a2 2 0 0 0 -2 2v12a2 2 0 0 0 2 2h7a2 2 0 0 0 2 -2v-2" /><path d="M9 12h12l-3 -3" /><path d="M18 15l3 -3" /></svg></a>
            <?php } ?>
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
            <div class="input-group">
              <input type="text" class="form-control rounded-start-3" placeholder="Search in <?= $request_uri ?>*" id="search">
              <select class="form-select rounded-end-3" aria-label="Engine" id="searchengine" style="max-width:7em">
                $[if `process.env.SEARCH_ENGINE.includes("s")`]$
                <option value="s">Simple</option>
                $[end]$
                $[if `process.env.SEARCH_ENGINE.includes("g")`]$
                <option value="g">Glob</option>
                $[end]$
                $[if `process.env.SEARCH_ENGINE.includes("r")`]$
                <option value="r">Regex</option>
                $[end]$
              </select>
            </div>
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
        </div>
      </div>
      <div class="rounded container card  px-3 d-none" style="border-top: none !important;border-top-right-radius: 0 !important;border-top-left-radius: 0 !important;" id="resultstree"></div>
      <div class="rounded container card  px-3" style="border-top: none !important;border-top-right-radius: 0 !important;border-top-left-radius: 0 !important;" id="filetree">
        
        <?php
        $now = new DateTime();
        foreach ($sorted as $file) {
          $fileDate = new DateTime($file->modified_date);
          $diff = $now->diff($fileDate)->days;
        ?>
        <a data-turbo-prefetch="<?= $file->is_dir ? "${{env:PREFETCH_FOLDERS}}$" : "${{env:PREFETCH_FILES}}$" ?>" data-turbo-action="advance" data-file-selected="0" data-file-isdir="<?= $file->is_dir ? "1" : "0" ?>" data-auth-required="<?= ($file->is_dir && $file->auth_required) ? "1" : "0" ?>" data-auth-locked="<?= ($file->is_dir && $file->auth_locked) ? "1" : "0" ?>" data-file-name="<?= $file->name ?>" data-file-dl="$[if `process.env.DOWNLOAD_COUNTER === "true"`]$<?= $file->dl_count ?>$[end]$" data-file-size="<?= $file->size ?>" data-file-mod="<?= $file->modified_date ?>"  href="${{`process.env.BASE_PATH ?? ''`}}$<?= $file->url ?><?= /* extra slash for dirs */ $file->is_dir ? "/" : "" ?>" class="row db-row py-2 db-file">
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
                // per-file password protection via .dbmeta.json was removed in favor of folder-level .access.json
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
    <div class="card rounded p-3 markdown-body-light markdown-body-dark" id="readme">
      <?= $readme_render ?>
    </div>
  </div>
  <?php 
  }
  ?>
  $[end]$

  <?php if ($max_pages > 1) { ?>
  <div class="container pb-3" style="display:flex;justify-content:center;">
    <nav aria-label="Page navigation example">
    <ul class="pagination">
      <li class="page-item"><a data-turbo-prefetch="false" class="page-link <?= $current_page <= 1 ? "disabled" : "" ?>" href="${{`process.env.BASE_PATH ?? ''`}}$<?= $request_uri . "?p=" . ($current_page - 1) ?>"><svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="iconX icon-tabler icons-tabler-outline icon-tabler-chevron-left"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M15 6l-6 6l6 6" /></svg></a></li>
      <?php foreach ($pages as $p) { ?>
      <li class="page-item"><a data-turbo-prefetch="false" class="page-link <?= $p == $current_page ? "active" : "" ?> <?= $p == ".." ? "disabled" : "" ?>" href="${{`process.env.BASE_PATH ?? ''`}}$<?= $request_uri . "?p=" . ($p) ?>"><?= $p ?></a></li>
      <?php } ?>
      <li class="page-item"><a data-turbo-prefetch="false" class="page-link <?= $current_page >= $max_pages ? "disabled" : "" ?>" href="${{`process.env.BASE_PATH ?? ''`}}$<?= $request_uri . "?p=" . ($current_page + 1) ?>"><svg  xmlns="http://www.w3.org/2000/svg"  width="16"  height="16"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="iconX icon-tabler icons-tabler-outline icon-tabler-chevron-right"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M9 6l6 6l-6 6" /></svg></a></li>
    </ul>
    </nav>
  </div>
  <?php } ?>

  <div class="mt-auto">
    <div class="container py-2 text-center" id="footer">
      Displaying <?= max(1, $page_start_offset) ?>-<?= min($page_start_offset - 1 + ${{`process.env.PAGINATION_PER_PAGE`}}$, $total_items) ?> of <?= $total_items ?> | <?= human_filesize($total_size) ?> $[if `process.env.TIMING === "true"`]$| <?= (hrtime(true) - $time_start)/1000000 ?> ms $[end]$$[if `process.env.API === "true"`]$| <a href="<?= '/' . implode(separator: '/', array: $url_parts) . '?ls' ?>" target="_blank"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-api"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 13h5" /><path d="M12 16v-8h3a2 2 0 0 1 2 2v1a2 2 0 0 1 -2 2h-3" /><path d="M20 8v8" /><path d="M9 16v-5.5a2.5 2.5 0 0 0 -5 0v5.5" /></svg></a>$[end]$<br>
    <span style="opacity:0.8"><span style="opacity: 0.8;">Powered by</span>  <a href="https://dir.adriansoftware.de" class="text-decoration-none text-primary" target="_blank">dir-browser</a> v<?= VERSION ?></span>  
    </div>
  </div>

  $[if `process.env.LAYOUT === "popup"`]$
  <div class="modal rounded fade" style="background-color:rgba(0, 0, 0, 0.2);" id="file-popup" data-bs-backdrop="static" data-bs-keyboard="false" tabindex="-1" aria-labelledby="staticBackdropLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-lg-down modal-lg">
      <div class="modal-content rounded ">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="staticBackdropLabel">Modal title</h1>
          <button type="button" class="btn-close" id="file-popup-x" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="file-popup-preview" class="mb-3">
            <div class="text-body-secondary">Loading…</div>
          </div>
          <div class="border-top pt-2">
            <div class="fw-semibold mb-2">Metadata</div>
            <dl class="row mb-0" id="file-popup-meta"></dl>
          </div>
        </div>
        <div class="modal-footer">
        <!-- TODO: add copy button if kind == text -->
        <button id="file-popup-copy" type="button" class="btn rounded btn-secondary" disabled><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-copy"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 7m0 2.667a2.667 2.667 0 0 1 2.667 -2.667h8.666a2.667 2.667 0 0 1 2.667 2.667v8.666a2.667 2.667 0 0 1 -2.667 2.667h-8.666a2.667 2.667 0 0 1 -2.667 -2.667z" /><path d="M4.012 16.737a2.005 2.005 0 0 1 -1.012 -1.737v-10c0 -1.1 .9 -2 2 -2h10c.75 0 1.158 .385 1.5 1" /></svg> Copy Text</button>

        ${{`process.env.API === "true" ? '<a id="file-info-url-api" href="?info" target="_blank" type="button" class="btn rounded btn-secondary"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-code"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M7 8l-4 4l4 4" /><path d="M17 8l4 4l-4 4" /><path d="M14 4l-4 16" /></svg> API</a>' : ''`}}$
          <a id="file-info-url" type="button" class="btn rounded btn-primary"><svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-download"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2 -2v-2" /><path d="M7 11l5 5l5 -5" /><path d="M12 4l0 12" /></svg> Download</a>
        </div>
      </div>
    </div>
  </div>
  $[end]$

  <!-- Password auth popup for protected folders (keeps full-page prompt as fallback) -->
  <div class="modal rounded fade" style="background-color:rgba(0, 0, 0, 0.2);" id="auth-popup" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-scrollable modal-fullscreen-sm-down" style="max-width: 520px;">
      <div class="modal-content rounded ">
        <div class="modal-header">
          <h1 class="modal-title fs-5" id="auth-popup-title">Protected folder</h1>
          <button type="button" class="btn-close" id="auth-popup-x" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="text-body-secondary mb-2">Enter the password to access this folder.</div>
          <div class="alert alert-danger py-2 d-none" id="auth-popup-error" role="alert">Incorrect password.</div>
          <form id="auth-popup-form" data-turbo="false">
            <input type="password" class="form-control mb-2 rounded" id="auth-popup-key" name="key" autocomplete="current-password" required>
            <button type="submit" class="btn rounded btn-primary key-icon form-control" id="auth-popup-submit">Unlock</button>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Toasts -->
  <div class="toast-container position-fixed bottom-0 end-0 p-3 border-4 rounded" style="z-index: 1100;">
    <div id="batch-download-toast" class="toast border-4 rounded" role="alert" aria-live="polite" aria-atomic="true">
      <div class="toast-header">
        <svg  xmlns="http://www.w3.org/2000/svg"  width="24"  height="24"  viewBox="0 0 24 24"  fill="none"  stroke="currentColor"  stroke-width="2"  stroke-linecap="round"  stroke-linejoin="round"  class="icon icon-tabler icons-tabler-outline icon-tabler-folder-down"><path stroke="none" d="M0 0h24v24H0z" fill="none"/><path d="M12 19h-7a2 2 0 0 1 -2 -2v-11a2 2 0 0 1 2 -2h4l3 3h7a2 2 0 0 1 2 2v3.5" /><path d="M19 16v6" /><path d="M22 19l-3 3l-3 -3" /></svg>
        <strong class="me-auto ms-1" id="batch-download-toast-header">...</strong>
        <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
      </div>
      <div class="toast-body" id="batch-download-toast-body">...</div>
    </div>
  </div>

  <!-- Powered by https://github.com/adrianschubek/dir-browser -->
  <script data-turbo-eval="false">
    const showToast = (message, header) => {
      try {
        const toastEl = document.getElementById('batch-download-toast');
        const toastBody = document.getElementById('batch-download-toast-body');
        if (!toastEl || !toastBody) return;
        toastBody.textContent = String(message ?? '');
        if (header) {
          const toastHeader = document.getElementById('batch-download-toast-header');
          if (toastHeader) {
            toastHeader.textContent = String(header);
          }
        }
        if (typeof bootstrap === 'undefined' || !bootstrap.Toast) return;
        bootstrap.Toast.getOrCreateInstance(toastEl, { delay: 13000 }).show();
      } catch (e) {
        // no-op
      }
    };

    const copyTextToClipboard = async (text) => {
      if (typeof text !== 'string' || text.length === 0) return false;

      // Prefer async clipboard API when available (requires secure context).
      try {
        if (navigator.clipboard && window.isSecureContext) {
          await navigator.clipboard.writeText(text);
          return true;
        }
      } catch (e) {
        // Fall back below.
      }

      // HTTP / non-secure fallback using execCommand.
      try {
        const textarea = document.createElement('textarea');
        textarea.value = text;
        textarea.setAttribute('readonly', '');
        textarea.style.position = 'fixed';
        textarea.style.top = '-1000px';
        textarea.style.left = '-1000px';
        document.body.appendChild(textarea);
        textarea.select();
        textarea.setSelectionRange(0, textarea.value.length);
        const ok = document.execCommand('copy');
        document.body.removeChild(textarea);
        return ok;
      } catch (e) {
        return false;
      }
    };

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
    const HASH_MAX_FILE_SIZE_MB = Number('${{`process.env.HASH_MAX_FILE_SIZE_MB ?? ""`}}$');
    const HASH_MAX_FILE_SIZE_BYTES = Number.isFinite(HASH_MAX_FILE_SIZE_MB) && HASH_MAX_FILE_SIZE_MB > 0
      ? Math.floor(HASH_MAX_FILE_SIZE_MB * 1024 * 1024)
      : null;

    const getHashViaApi = async (url) => {
      const res = await fetch(url);
      if (!res.ok) {
        if (res.status === 413) throw new Error('too_large');
        throw new Error('request_failed');
      }
      const data = await res.json();
      const hash = data.hash_${{`process.env.HASH_ALGO`}}$;
      if (hash === null || hash === undefined || String(hash).length === 0) throw new Error('unavailable');
      const text = String(hash ?? '');
      await copyTextToClipboard(text);
      return text;
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
      const basePath = '${{`process.env.BASE_PATH ?? ''`}}$';
      document.querySelectorAll('.db-file').forEach((file) => {
        if ((all || file.getAttribute('data-file-selected') === "1") && file.getAttribute('data-file-name') !== "..") {
          const input = document.createElement('input');
          input.type = 'hidden';
          input.name = 'download_batch[]';
          // Always send just the path (relative to dir-browser PUBLIC_FOLDER), without BASE_PATH and without query params.
          const href = file.getAttribute('href');
          const url = new URL(href, window.location.origin);
          let path = url.pathname;
          if (basePath && path.startsWith(basePath)) {
            path = path.slice(basePath.length) || '/';
          }
          input.value = path;
          form.appendChild(input);          
        }
      });
      document.body.appendChild(form);
      form.submit();
      document.body.removeChild(form);
    }
    const downloadThisFolder = async (path) => {
      console.log("Download this folder " + path);
      showToast('Preparing batch download. This may take a few moments...', path);
      await download(true);
    }
    const downloadMultiple = async () => {
      console.log("Download multiple");
      showToast('Preparing batch download. This may take a few moments...', path);
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
      if (result.is_dir) {
        item.setAttribute('data-auth-required', result.auth_required ? '1' : '0');
        item.setAttribute('data-auth-locked', result.auth_locked ? '1' : '0');
      }
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
      const searchengine = document.querySelector('#searchengine').value;
      if (search.length === 0) return;
      const api = await fetch(`${{`process.env.BASE_PATH ?? ''`}}$?q=${search}&e=${searchengine}`).then((res) => res.json());
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

    // Auth popup for protected folders
    const authPopupState = {
      targetUrl: '',
      title: 'Protected folder'
    };

    const showAuthPopup = (url, title) => {
      authPopupState.targetUrl = url;
      authPopupState.title = title || 'Protected folder';
      const popup = document.querySelector('#auth-popup');
      const titleNode = document.querySelector('#auth-popup-title');
      const keyInput = document.querySelector('#auth-popup-key');
      const err = document.querySelector('#auth-popup-error');
      const submit = document.querySelector('#auth-popup-submit');
      if (!popup) return;
      if (titleNode) titleNode.textContent = authPopupState.title;
      if (err) err.classList.add('d-none');
      if (submit) submit.disabled = false;
      if (keyInput) keyInput.value = '';

      popup.classList.add('d-block');
      popup.classList.add('show');
      setTimeout(() => keyInput?.focus(), 50);
    };

    const hideAuthPopup = () => {
      const popup = document.querySelector('#auth-popup');
      if (!popup) return;
      popup.classList.remove('d-block');
      popup.classList.remove('show');
    };

    document.addEventListener('click', (e) => {
      const a = e.target?.closest ? e.target.closest('a.db-file') : null;
      if (!a) return;
      // Only for folders that are locked.
      if (a.getAttribute('data-file-isdir') !== '1') return;
      if (a.getAttribute('data-auth-locked') !== '1') return;
      // If multiselect mode is on, keep existing multiselect behavior.
      if ((localStorage.getItem('multiSelectMode') ?? 'false') === 'true') return;
      e.preventDefault();
      const name = a.getAttribute('data-file-name') || 'Protected folder';
      showAuthPopup(a.href, name);
    });

    // Delegated handlers (survive Turbo navigation / DOM swaps)
    document.addEventListener('click', (e) => {
      const t = e.target;
      if (!t) return;
      if (t.id === 'auth-popup-x') {
        e.preventDefault();
        hideAuthPopup();
        return;
      }
      if (t.id === 'auth-popup') {
        hideAuthPopup();
        return;
      }
    });

    document.addEventListener('submit', async (e) => {
      const form = e.target;
      if (!form || form.id !== 'auth-popup-form') return;
      e.preventDefault();

      const keyInput = document.querySelector('#auth-popup-key');
      const err = document.querySelector('#auth-popup-error');
      const submit = document.querySelector('#auth-popup-submit');
      const key = keyInput?.value ?? '';
      if (err) {
        err.textContent = 'Incorrect password.';
        err.classList.add('d-none');
      }
      if (!authPopupState.targetUrl) return;
      if (!key || key.length === 0) return;

      try {
        if (submit) submit.disabled = true;
        const res = await fetch(authPopupState.targetUrl, {
          method: 'POST',
          headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
          body: 'key=' + encodeURIComponent(key),
          credentials: 'same-origin',
        });

        if (res.status === 401) {
          if (err) err.classList.remove('d-none');
          if (submit) submit.disabled = false;
          keyInput?.focus();
          keyInput?.select();
          return;
        }

        // Success: cookie should be set; navigate normally.
        window.location.href = authPopupState.targetUrl;
      } catch (ex) {
        if (err) {
          err.textContent = 'Authentication failed. Please try again.';
          err.classList.remove('d-none');
        }
        if (submit) submit.disabled = false;
      }
    });

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
    $[if `process.env.LAYOUT === "popup"`]$
      const popupState = {
        preview: {
          kind: 'none',
          text: ''
        },
        apiInfoUrl: ''
      };

      const fileUrlWithParam = (urlString, key, value) => {
        const url = new URL(urlString, window.location.href);
        url.searchParams.set(key, value);
        return url.toString();
      };

      const setMeta = (entries) => {
        const meta = document.querySelector('#file-popup-meta');
        meta.innerHTML = '';
        entries.forEach(({ label, value }) => {
          const dt = document.createElement('dt');
          dt.className = 'col-4 text-body-secondary';
          dt.textContent = label;
          const dd = document.createElement('dd');
          dd.className = 'col-8';
          if (value instanceof Node) {
            dd.appendChild(value);
          } else {
            dd.textContent = String(value ?? '');
          }
          meta.appendChild(dt);
          meta.appendChild(dd);
        });
      };

      const updateCopyButton = () => {
        const btn = document.querySelector('#file-popup-copy');
        if (!btn) return;
        const canCopy = (popupState.preview.kind === 'text' || popupState.preview.kind === 'json' || popupState.preview.kind === 'csv')
          && typeof popupState.preview.text === 'string'
          && popupState.preview.text.length > 0;
        btn.disabled = !canCopy;
      };

      const copyPreviewToClipboard = async () => {
        await copyTextToClipboard(popupState.preview.text || '');
      };

      const setPreview = (preview, rawUrl) => {
        const node = document.querySelector('#file-popup-preview');
        node.innerHTML = '';

        popupState.preview.kind = preview?.kind ?? 'none';
        popupState.preview.text = '';
        updateCopyButton();

        if (!preview || preview.kind === 'none') {
          const el = document.createElement('div');
          el.className = 'text-body-secondary';
          el.textContent = 'No preview available for this file.';
          node.appendChild(el);
          return;
        }

        if (preview.kind === 'image') {
          const img = document.createElement('img');
          img.className = 'img-fluid rounded';
          img.alt = 'Preview';
          img.src = rawUrl;
          node.appendChild(img);
          return;
        }

        if (preview.kind === 'video') {
          const video = document.createElement('video');
          video.className = 'w-100 rounded';
          video.controls = true;
          const source = document.createElement('source');
          source.src = rawUrl;
          source.type = preview.mime || 'video/mp4';
          video.appendChild(source);
          node.appendChild(video);
          return;
        }

        if (preview.kind === 'audio') {
          const audio = document.createElement('audio');
          audio.className = 'w-100';
          audio.controls = true;
          audio.preload = 'metadata';
          const source = document.createElement('source');
          source.src = rawUrl;
          source.type = preview.mime || 'audio/mpeg';
          audio.appendChild(source);
          node.appendChild(audio);
          return;
        }

        const pre = document.createElement('pre');
        pre.className = 'bg-body-tertiary p-2 rounded small';
        pre.style.maxHeight = '50vh';
        pre.style.overflow = 'auto';
        pre.textContent = preview.text || '';
        node.appendChild(pre);

        popupState.preview.text = pre.textContent;
        updateCopyButton();

        if (preview.truncated) {
          const note = document.createElement('div');
          note.className = 'text-body-secondary small mt-1';
          note.textContent = 'Preview truncated.';
          node.appendChild(note);
        }
      };

      const setFileinfo = async (data) => {
        document.querySelector('#file-popup .modal-title').innerText = data.name;
        const apiBtn = document.querySelector('#file-info-url-api');
        popupState.apiInfoUrl = fileUrlWithParam(data.url, 'info', '');
        if (apiBtn) apiBtn.href = popupState.apiInfoUrl;
        document.querySelector('#file-info-url').href = data.url;

        const popup = document.querySelector('#file-popup');
        popup.classList.add('d-block');
        popup.classList.add('show');

        const previewNode = document.querySelector('#file-popup-preview');
        previewNode.innerHTML = '<div class="text-body-secondary">Loading…</div>';
        setMeta([]);

        try {
          const previewUrl = fileUrlWithParam(data.url, 'preview', '1');
          const res = await fetch(previewUrl);
          if (!res.ok) throw new Error('Preview request failed');
          const payload = await res.json();
          const rawUrl = fileUrlWithParam(data.url, 'raw', '1');
          setPreview(payload.preview, rawUrl);

          const modified = payload.modified ? new Date(payload.modified).toLocaleString() : '';
          const entries = [
            { label: 'Size', value: payload.size_human ?? String(payload.size ?? '') },
            { label: 'Modified', value: modified },
            { label: 'Downloads', value: String(payload.downloads ?? 0) },
            { label: 'MIME', value: payload.mime ?? '' },
          ];
          $[end]$
          $[if `process.env.LAYOUT === "popup" && process.env.HASH === "true"`]$
          // Show hash entry; actual hash is fetched on demand via API.
          if (typeof getHashViaApi === 'function') {
            const fileSize = Number(payload.size ?? 0);
            const hashingTooLarge = HASH_MAX_FILE_SIZE_BYTES !== null && Number.isFinite(fileSize) && fileSize > HASH_MAX_FILE_SIZE_BYTES;
            if (hashingTooLarge) {
              const note = document.createElement('span');
              note.className = 'text-body-secondary';
              note.textContent = `Too large to hash (>${HASH_MAX_FILE_SIZE_MB} MB)`;
              entries.push({ label: 'Hash (${{`process.env.HASH_ALGO`}}$)', value: note });
            } else {
              const link = document.createElement('a');
              link.href = '#';
              link.className = 'link-primary link-offset-2 link-underline-opacity-25 link-underline-opacity-100-hover';
              link.textContent = 'Click to calculate hash';
              link.addEventListener('click', async (e) => {
                link.textContent = 'Calculating…';
                e.preventDefault();
                if (!popupState.apiInfoUrl) return;
                try {
                  const hash = await getHashViaApi(popupState.apiInfoUrl);
                  link.textContent = `${hash} (copied to clipboard)`;
                } catch (err) {
                  link.textContent = err && String(err.message) === 'too_large' ? 'Too large to hash' : 'Hash unavailable';
                }
              });
              entries.push({ label: 'Hash (${{`process.env.HASH_ALGO`}}$)', value: link });
            }
          }
          $[end]$
          $[if `process.env.LAYOUT === "popup"`]$

          setMeta(entries);
        } catch (err) {
          previewNode.innerHTML = '<div class="text-danger">Failed to load preview.</div>';
        }
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

    // if localstoage has sort:order:name, apply it
    if (localStorage.getItem("sort:order:name")) {
      sort('name', localStorage.getItem("sort:order:name") === "desc");
    } else if (localStorage.getItem("sort:order:dl")) {
      sort('dl', localStorage.getItem("sort:order:dl") === "desc");
    } else if (localStorage.getItem("sort:order:size")) {
      sort('size', localStorage.getItem("sort:order:size") === "desc");
    } else if (localStorage.getItem("sort:order:mod")) {
      sort('mod', localStorage.getItem("sort:order:mod") === "desc");
    }

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

    $[if `process.env.LAYOUT === "popup"`]$
    (() => {
      const copyBtn = document.querySelector('#file-popup-copy');
      if (copyBtn && copyBtn.dataset.dbBoundClick !== '1') {
        copyBtn.dataset.dbBoundClick = '1';
        copyBtn.addEventListener('click', async (e) => {
          e.preventDefault();
          await copyPreviewToClipboard();
        });
      }
    })();

    document.querySelectorAll('.db-file').forEach((item) => {
      // skip folders
      if (item.getAttribute('data-file-isdir') === '1') {
        return;
      }
      item.addEventListener('click', async (e) => {
        // If multiselect mode is on, keep existing multiselect behavior.
        if ((localStorage.getItem("multiSelectMode") ?? "false") === "true") return;
        e.preventDefault();

        // only do this on reload click button refreshFileinfo()
        // const data = await fetch(item.href + "?info").then((res) => res.json());
        // alert(JSON.stringify(data, null, 2));

        await setFileinfo({
          name: item.getAttribute('data-file-name'),
          url: item.href
        });
      })
    });
    document.querySelector('#file-popup').addEventListener('click', (e) => {
      if (e.target === document.querySelector('#file-popup')) {
        document.querySelector('#file-popup').classList.remove("d-block");
        document.querySelector('#file-popup').classList.remove("show");
      }
    });
    document.querySelector('#file-popup-x').addEventListener('click', (e) => {
      document.querySelector('#file-popup').classList.remove("d-block");
      document.querySelector('#file-popup').classList.remove("show");
    });
    $[end]$

    $[if `process.env.SEARCH === "true"`]$
    document.querySelector('#search').addEventListener('input', search);
    document.querySelector('#searchengine').addEventListener('change', search);
    $[end]$

    document.querySelector('#name').addEventListener('click', (e) => {
      e.preventDefault();
      localStorage.setItem("sort:order:name", localStorage.getItem("sort:order:name") === "asc" ? "desc" : "asc");
      sort('name', localStorage.getItem("sort:order:name") === "desc");
      // reset other sort orders
      localStorage.removeItem("sort:order:dl");
      localStorage.removeItem("sort:order:size");
      localStorage.removeItem("sort:order:mod");
    });

    $[if `process.env.DOWNLOAD_COUNTER === "true"`]$
    document.querySelector('#dl').addEventListener('click', (e) => {
      e.preventDefault();
      localStorage.setItem("sort:order:dl", localStorage.getItem("sort:order:dl") === "asc" ? "desc" : "asc");
      sort('dl', localStorage.getItem("sort:order:dl") === "desc");
      // reset other sort orders
      localStorage.removeItem("sort:order:name");
      localStorage.removeItem("sort:order:size");
      localStorage.removeItem("sort:order:mod");
    });
    $[end]$

    document.querySelector('#size').addEventListener('click', (e) => {
      e.preventDefault();
      localStorage.setItem("sort:order:size", localStorage.getItem("sort:order:size") === "asc" ? "desc" : "asc");
      sort('size', localStorage.getItem("sort:order:size") === "desc");
      // reset other sort orders
      localStorage.removeItem("sort:order:name");
      localStorage.removeItem("sort:order:dl");
      localStorage.removeItem("sort:order:mod");
    });

    document.querySelector('#mod').addEventListener('click', (e) => {
      e.preventDefault();
      localStorage.setItem("sort:order:mod", localStorage.getItem("sort:order:mod") === "asc" ? "desc" : "asc");
      sort('mod', localStorage.getItem("sort:order:mod") === "desc");
      // reset other sort orders
      localStorage.removeItem("sort:order:name");
      localStorage.removeItem("sort:order:dl");
      localStorage.removeItem("sort:order:size");
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
  <script data-turbo-eval="false" src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js"></script>

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
