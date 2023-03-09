<?php

define('PUBLIC_FOLDER', __DIR__ . '/public');

$url_parts = array_filter(explode(separator: '/', string: $_SERVER['REQUEST_URI']), fn ($part) => $part !== '');

echo $local_path = PUBLIC_FOLDER . $_SERVER['REQUEST_URI'];

echo "Ist dir ?? " . ($path_is_dir = is_dir($local_path)) . "\n\n\n";
/* if (!$path_is_dir) {
  return;
} */

var_dump($url_parts);

// echo phpinfo();
?>
<!doctype html>
<html lang="en" data-bs-theme="dark">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Bootstrap demo</title>
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-GLhlTQ8iRABdZLl6O3oVMWSktQOp6b7In1Zl3/Jr59b6EGGoI1aFkw7cmDA6j6gD" crossorigin="anonymous">
</head>

<body>
  <h1>Hello, world!</h1>

  <div class="container">
    <ul>
      <?php
      foreach (($files = scandir($local_path)) as $file) {
        // skip current folder '.'
        if ($file === '.') continue;

        $url = '/' . implode(separator: '/', array: $url_parts) . (count($url_parts) !== 0 ? '/' : '') /* fixes // at root url */ . $file;
        echo $url;
        $type = is_dir($local_path . '/' . $file) ? 'dir' : 'file';
        echo "<li><a href=\"$url\"> $file ($type)</a></li>";
      }
      ?>
    </ul>
  </div>
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0-alpha1/dist/js/bootstrap.bundle.min.js" integrity="sha384-w76AqPfDkMBDXo30jS1Sgez6pr3x5MlQ1ZAGC+nuZB+EYdgRZgiwxhTBTkF7CXvN" crossorigin="anonymous"></script>
</body>

</html>