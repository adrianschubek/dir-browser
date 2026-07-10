<?php

define('VERSION', '${{`process.env.DIRBROWSER_VERSION`}}$');

define('PUBLIC_FOLDER', __DIR__ . '/public');

$request_path = rawurldecode((string) (parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?? '/'));
if ($request_path === '/__health') {
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(['status' => 'ok', 'version' => VERSION], JSON_THROW_ON_ERROR);
  die();
}

$[if `process.env.PASSWORD_URL_KEY !== undefined`]$
if (!isset($_GET['auth']) || !is_string($_GET['auth']) || $_GET['auth'] === '' || !hash_equals('${{`process.env.PASSWORD_URL_KEY`}}$', $_GET['auth'])) {
  header('HTTP/1.0 401 Unauthorized');
  header('Content-Type: text/plain; charset=utf-8');
  echo "Authentication required. This dir-browser instance requires ?auth=<key>.";
  die;
}
$[end]$

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
$[end]$

require_once __DIR__ . '/app/bootstrap.php';

$pathPolicy = new PathPolicy(PUBLIC_FOLDER);
$authSessions = new AuthSessionStore();
$accessControl = new AccessControl($pathPolicy, $authSessions);
$metadataRepository = new MetadataRepository();
$fileRepository = new FileRepository($pathPolicy, $accessControl, $metadataRepository);
$searchService = new SearchService($pathPolicy, $fileRepository, $accessControl);
$batchDownloadService = new BatchDownloadService($pathPolicy, $fileRepository);

if (isset($_GET['logout'])) {
  $accessControl->clearSession();
  $path = '${{`process.env.BASE_PATH ?? ''`}}$' ?: '/';
  parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $query);
  unset($query['logout']);
  $queryString = http_build_query($query);
  http_response_code(303);
  header('Location: ' . $path . ($queryString !== '' ? '?' . $queryString : ''));
  die();
}

$request_uri = rawurldecode((string) (parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/'));
$request_href_path = $pathPolicy->encodeUrlPath($request_uri);
$url_parts = array_values(array_filter(explode('/', $request_uri), static fn ($part) => $part !== ''));
$local_path = $pathPolicy->resolve($request_uri);

$path_is_dir = $local_path !== false && is_dir($local_path);
$max_pages = 1;
$page_start_offset = 0;
$total_items = 0;
$total_size = 0;
$pages = [];
$current_page = 1;
$sorted = [];

if ($local_path === false) goto skip;

$[if `process.env.SEARCH === "true"`]$
if (array_key_exists('q', $_GET) || array_key_exists('e', $_GET)) {
  if (!$path_is_dir || !is_string($_GET['q'] ?? null) || !is_string($_GET['e'] ?? null)) {
    json_response(['error' => 'Invalid search request'], 400);
  }

  $access = $accessControl->statusForPath($local_path);
  if ($access['hidden']) json_response(['error' => 'Not found'], 404);
  if ($access['requires_password'] && !$access['authorized']) json_response(['error' => 'Authentication required'], 401);

  $engine = $_GET['e'];
  if (!in_array($engine, explode(',', "${{`process.env.SEARCH_ENGINE`}}$"), true)) {
    json_response(['error' => 'Invalid search engine'], 400);
  }

  try {
    json_response($searchService->search($_GET['q'], $local_path, $engine));
  } catch (SearchValidationException $exception) {
    json_response(['error' => $exception->getMessage()], 400);
  } catch (Throwable) {
    json_response(['error' => 'Search failed'], 500);
  }
}
$[end]$

// local path exists
if ($path_is_dir) {
  $[if `process.env.DOWNLOAD_COUNTER === "true"`]$
  $redis = redis_client();
  $[end]$

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
      if (request_access_key() !== null && (isset($_POST['key']) || isset($_GET['key']))) {
        $accessControl->grant($access);
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
    $meta = null;

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
    $meta = $metadataRepository->forPath($path);
    if ($metadataRepository->isHidden($meta)) continue;
    if ($meta !== null && ($description = $metadataRepository->escapedDescription($meta)) !== null) {
      $meta->description = $description;
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
  $counter_urls = array_map(fn ($file) => $file->url, array_merge($sorted_folders, $sorted_files));
  $dl_counters = $redis?->mget($counter_urls) ?: array_fill(0, count($counter_urls), 0);
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
    $all_counters = $redis?->mget($urls) ?: array_fill(0, count($urls), 0);
    $counters_map = $urls === [] ? [] : array_combine($urls, $all_counters);
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
    json_response($info);
  }
  $[end]$

  // is batch download?
  $[if `process.env.BATCH_DOWNLOAD === "true"`]$
  if (array_key_exists('download_batch', $_POST)) {
    if (!is_array($_POST['download_batch'])) json_response(['error' => 'download_batch must be an array'], 400);
    downloadBatch($_POST['download_batch']);
  }
  $[end]$

  // readme
  $[if `process.env.README_RENDER === "true"`]$
  $readme = null;
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
    $config = ['allow_unsafe_links' => false];

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
  $max_pages = max(1, (int) ceil(count($sorted) / ${{`process.env.PAGINATION_PER_PAGE`}}$));
  $current_page = min($current_page, $max_pages);
  $page_start_offset = ($current_page - 1) * ${{`process.env.PAGINATION_PER_PAGE`}}$;
  $sorted = array_slice($sorted, $page_start_offset, ${{`process.env.PAGINATION_PER_PAGE`}}$);
  $displayed_item_count = count(array_filter($sorted, static fn (File $file) => $file->name !== '..'));
  $display_start = $total_items === 0 ? 0 : max(1, $page_start_offset);
  $display_end = $total_items === 0 ? 0 : min($display_start + $displayed_item_count - 1, $total_items);

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
  $internal_redirect = '/__internal_public__' . $pathPolicy->encodeUrlPath($relative_path);

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
      if (request_access_key() !== null && (isset($_POST['key']) || isset($_GET['key']))) {
        $accessControl->grant($access);
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
  if ($metadataRepository->isInternalMetadataPath($local_path)) goto skip;
  $meta = $metadataRepository->forPath($local_path);
  $[end]$

  $[if `process.env.HASH`]$
  // only allow download if requested hash matches actual hash
  $requested_hash = $_GET['hash'] ?? null;
  if (${{`process.env.HASH_REQUIRED === "true" ? "true ||" : ""`}}$ $requested_hash !== null || $metadataRepository->requiresHash($meta ?? null)) {
    if (!hashing_allowed_for_file($local_path)) {
      http_response_code(413);
      $limitMb = getenv('HASH_MAX_FILE_SIZE_MB');
      die("<b>Hashing disabled.</b> File exceeds HASH_MAX_FILE_SIZE_MB (" . htmlspecialchars((string) $limitMb) . " MB).");
    }
    if (!is_string($requested_hash) || $requested_hash === '') {
      http_response_code(403);
      die("<b>Access denied.</b> Hash is required for this file.");
    }
    if (!hash_equals(hash_file('${{`process.env.HASH_ALGO`}}$', $local_path), $requested_hash)) {
      http_response_code(403);
      die("<b>Access denied.</b> Supplied hash does not match actual file hash.");
    }
  }
  $[end]$

  // increment redis view counter
  $[if `process.env.DOWNLOAD_COUNTER === "true"`]$
  $redis = redis_client();
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
      "downloads" => ${{`process.env.DOWNLOAD_COUNTER === "true" ? "intval($redis?->get($relative_path) ?: 0)" : "0"`}}$,
      "hash_${{`process.env.HASH_ALGO`}}$" => $hash_value
    ];
    json_response($info);
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
    } elseif ($mime === "application/pdf" || $ext === "pdf") {
      $kind = "pdf";
    } elseif (str_starts_with($mime, "video/")) {
      $kind = "video";
    } elseif (str_starts_with($mime, "audio/") || in_array($ext, ["mp3", "m4a", "aac", "wav", "ogg", "oga", "opus", "flac"])) {
      $kind = "audio";
    } elseif ($mime === "application/json" || $ext === "json") {
      $kind = "json";
    } elseif ($mime === "text/csv" || $ext === "csv") {
      $kind = "csv";
    } elseif ($ext === "md") {
      $kind = "markdown";
    } elseif (str_starts_with($mime, "text/") || in_array($ext, ["txt", "log", "yaml", "yml", "ini", "conf", "xml", "html", "css", "js", "ts", "php"])) {
      $kind = "text";
    }

    $preview = [
      "kind" => $kind,
      "mime" => $mime,
      "truncated" => false,
      "text" => null,
    ];

    // For media we don't inline bytes; client will request ?raw=1.
    if ($kind === "text" || $kind === "json" || $kind === "csv" || $kind === "markdown") {
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
        } elseif ($kind === "markdown") {
          if (class_exists('\\League\\CommonMark\\Environment\\Environment')) {
            try {
              $config = ['allow_unsafe_links' => false];
              $environment = new \League\CommonMark\Environment\Environment($config);
              $environment->addExtension(new \League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension());
              $environment->addExtension(new \League\CommonMark\Extension\Autolink\AutolinkExtension());
              ${{`!process.env.ALLOW_RAW_HTML ? "$environment->addExtension(new \\League\\CommonMark\\Extension\\DisallowedRawHtml\\DisallowedRawHtmlExtension());" : ""`}}$
              $environment->addExtension(new \League\CommonMark\Extension\Strikethrough\StrikethroughExtension());
              $environment->addExtension(new \League\CommonMark\Extension\Table\TableExtension());
              $environment->addExtension(new \League\CommonMark\Extension\TaskList\TaskListExtension());
              $converter = new \League\CommonMark\MarkdownConverter($environment);
              $preview["text"] = (string) $converter->convert($raw);
            } catch (Exception $e) {
              $preview["kind"] = "text";
              $preview["text"] = $raw;
            }
          } else {
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
      "downloads" => ${{`process.env.DOWNLOAD_COUNTER === "true" ? "intval($redis?->get($relative_path) ?: 0)" : "0"`}}$,
      "preview" => $preview,
    ];
    json_response($payload);
  }

  // Raw streaming for popup media previews without increasing download counter.
  if (isset($_REQUEST["raw"])) {
    header("Content-Type: ");
    header('X-Accel-Redirect: ' . $internal_redirect);
    die();
  }

  $[if `process.env.DOWNLOAD_COUNTER === "true"`]$
  $redis?->incr($relative_path);
  $[end]$
   
  // let nginx guess content type
  header("Content-Type: ");
  // let nginx handle file serving
  header('X-Accel-Redirect: ' . $internal_redirect);
  die();
} else {
  // local path does not exist
skip:
  http_response_code(404);
end: 
}
require __DIR__ . '/views/page.php';
