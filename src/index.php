<?php

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

?>
<!doctype html>
<html lang="en" data-bs-theme="dark">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bootstrap demo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">

  <script async defer src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
  <script async defer src="https://cdnjs.cloudflare.com/ajax/libs/turbolinks/5.0.0/turbolinks.min.js"></script>
  <!-- integrity="sha512-ifx27fvbS52NmHNCt7sffYPtKIvIzYo38dILIVHQ9am5XGDQ2QjSXGfUZ54Bs3AXdVi7HaItdhAtdhKz8fOFrA==" -->
</head>

<body>
  <h1><?= '/' . implode(separator: '/', array: $url_parts) ?></h1>

  <?php if (!$path_is_dir) { ?>
    <div class="alert alert-secondary" role="alert">
      <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-file-unknown" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
        <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
        <path d="M14 3v4a1 1 0 0 0 1 1h4"></path>
        <path d="M17 21h-10a2 2 0 0 1 -2 -2v-14a2 2 0 0 1 2 -2h7l5 5v11a2 2 0 0 1 -2 2z"></path>
        <path d="M12 17v.01"></path>
        <path d="M12 14a1.5 1.5 0 1 0 -1.14 -2.474"></path>
      </svg>
      Not Found
    </div>
  <?php } else { ?>
    <div class="container">
      <div class="list-group">
        <?php
        foreach (($files = scandir($local_path)) as $file) {
          // always skip current folder '.' or parent folder '..' if current path is root
          if ($file === '.' || $file === '..' && count($url_parts) === 0) continue;

          $url = '/' . implode(separator: '/', array: $url_parts) . (count($url_parts) !== 0 ? '/' : '') /* fixes // at root url */ . $file;

          $file_size = human_filesize(filesize($local_path . '/' . $file));

          $is_dir = is_dir($local_path . '/' . $file);

          $file_date = date('Y-m-d H:i:s', filemtime($local_path . '/' . $file));
        ?>
          <a href="<?= $url ?>" class="list-group-item list-group-item-action">
            <?php if ($file === "..") { ?>
              <svg xmlns="http://www.w3.org/2000/svg" class="icon icon-tabler icon-tabler-corner-left-up" width="24" height="24" viewBox="0 0 24 24" stroke-width="2" stroke="currentColor" fill="none" stroke-linecap="round" stroke-linejoin="round">
                <path stroke="none" d="M0 0h24v24H0z" fill="none"></path>
                <path d="M18 18h-6a3 3 0 0 1 -3 -3v-10l-4 4m8 0l-4 -4"></path>
              </svg>
            <?php } elseif ($is_dir) { ?>
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
            <?= $file ?> <?= !$is_dir ? "(" . $file_size . ")" : "" ?> (<?= $file_date ?>)
          </a>
        <?php
        }
        ?>
      </div>
    </div>
  <?php } ?>

</body>

</html>