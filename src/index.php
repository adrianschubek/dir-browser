<?php

define('VERSION', '0.1.0');

define('PUBLIC_FOLDER', __DIR__ . '/public');

function human_filesize($bytes, $decimals = 2): string
{
  $sz = 'BKMGTP';
  $factor = floor((strlen($bytes) - 1) / 3);
  return sprintf("%.{$decimals}f", $bytes / pow(1024, $factor)) . @$sz[$factor];
}

$url_parts = array_filter(explode(separator: '/', string: $_SERVER['REQUEST_URI']), fn ($part) => $part !== '');

$local_path = PUBLIC_FOLDER . $_SERVER['REQUEST_URI'];

$path_is_dir = is_dir($local_path);

class File
{
  public string $name;
  public string $url;
  public string $size;
  public bool $is_dir;
  public string $modified_date;
  public string $type;
}

/* @var array<File> */
$sorted = [];

$total_items = 0;
$total_size = 0;

// local path exists
if ($path_is_dir) {
  $sorted_files = [];
  $sorted_folders = [];
  foreach (($files = scandir($local_path)) as $file) {
    // always skip current folder '.' or parent folder '..' if current path is root
    if ($file === '.' || $file === '..' && count($url_parts) === 0) continue;

    $url = '/' . implode(separator: '/', array: $url_parts) . (count($url_parts) !== 0 ? '/' : '') /* fixes // -> / at root url */ . $file;

    $file_size = filesize($local_path . '/' . $file);

    $is_dir = is_dir($local_path . '/' . $file);

    $file_modified_date = date('Y-m-d H:i:s', filemtime($local_path . '/' . $file));

    $file_type = mime_content_type($local_path . '/' . $file);

    $item = new File();
    $item->name = $file;
    $item->url = $url;
    $item->size = human_filesize($file_size);
    $item->is_dir = $is_dir;
    $item->modified_date = $file_modified_date;
    $item->type = $file_type;
    if ($is_dir) {
      array_push($sorted_folders, $item);
    } else {
      array_push($sorted_files, $item);
    }

    // don't count parent folder
    if ($file !== "..") $total_items++;
    $total_size += $file_size;
  }

  $sorted = array_merge($sorted_folders, $sorted_files);
}

?>
<!doctype html>
<html lang="en" data-bs-theme="dark">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Dir Browser - <?= '/' . implode(separator: '/', array: $url_parts) ?></title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
</head>

<body class="d-flex flex-column min-vh-100">
  <nav class="navbar navbar-expand-lg bg-body-tertiary mb-3 shadow-sm">
    <div class="container-fluid">
      <span class="navbar-brand"><?= '/' . implode(separator: '/', array: $url_parts) ?></span>
      <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarSupportedContent" aria-controls="navbarSupportedContent" aria-expanded="false" aria-label="Toggle navigation">
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

  <div class="container pb-3">
    <?php if (!$path_is_dir) { ?>
      <div class="alert alert-secondary text-center" role="alert">
        <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-unknown" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
          <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
          <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
          <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"></path>
          <path d="M12 17v.01"></path>
          <path d="M12 14a1.5 1.5 0 1 0 -1.14 -2.474"></path>
        </svg>
        Not Found<br>
        <a class="btn btn-outline-secondary mt-2" href="/">Back to Home</a>
      </div>

    <?php } else { ?>
      <div class="list-group">
        <?php
        foreach ($sorted as $file) {
        ?>
          <a href="<?= $file->url ?>" class="list-group-item list-group-item-action d-flex gap-2">
            <?php if ($file->name === "..") { ?>
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-corner-left-up" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path d="M18 18h-6a3 3 0 0 1 -3 -3v-10l-4 4m8 0l-4 -4"></path>
              </svg>
            <?php } elseif ($file->is_dir) { ?>
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-folder-filled" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path d="M9 3a1 1 0 0 1 .608 .206l.1 .087l2.706 2.707h6.586a3 3 0 0 1 2.995 2.824l.005 .176v8a3 3 0 0 1 -2.824 2.995l-.176 .005h-14a3 3 0 0 1 -2.995 -2.824l-.005 -.176v-11a3 3 0 0 1 2.824 -2.995l.176 -.005h4z" stroke-width="0" fill="currentColor"></path>
              </svg>
            <?php } else { ?>
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
                <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"></path>
              </svg>
            <?php } ?>
            <?= $file->name ?>
            <span class="ms-auto">
              <?= !$file->is_dir ? $file->size : "" ?>
            </span>
            <span>
              <?= $file->modified_date ?>
            </span>
          </a>
        <?php
        }
        ?>

        <?php if (count($sorted_files) === 0 && (count($sorted_folders) === 0 || count($sorted_folders) === 1 && $sorted_folders[0]->name === "..")) { ?>
          <div class="list-group-item bg-body-tertiary text-center" role="alert">
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

  <div class="bg-body-tertiary mt-auto">
    <div class="container py-2 text-secondary text-center">
      <?= $total_items ?> Items | <?= human_filesize($total_size) ?> | Powered by <a href="https://github.com/adrianschubek/dir-browser" class="text-decoration-none" target="_blank">adrianschubek/dir-browser</a> | Version <?= VERSION ?>
    </div>
  </div>


  <script data-turbolinks-eval="false" async defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
  <script data-turbolinks-eval="false" async defer src="https://cdnjs.cloudflare.com/ajax/libs/turbolinks/5.0.0/turbolinks.min.js"></script>
  <!-- integrity="sha512-ifx27fvbS52NmHNCt7sffYPtKIvIzYo38dILIVHQ9am5XGDQ2QjSXGfUZ54Bs3AXdVi7HaItdhAtdhKz8fOFrA==" -->
  <script data-turbolinks-eval="false">
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
        document.documentElement.setAttribute('data-bs-theme', theme)
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
</body>

</html>