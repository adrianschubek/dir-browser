<!doctype html>
<html lang="en" data-theme="light">

<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta name="turbo-cache-control" content="no-cache">
  $[ifeq env:TRANSITION true]$
  <meta name="turbo-refresh-method" content="morph">
  <meta name="view-transition" content="same-origin" />
  $[end]$
  <title>${{`process.env.TITLE`}}$ - <?= htmlspecialchars('/' . implode(separator: '/', array: $url_parts), ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8') ?></title>
  <?php require __DIR__ . '/partials/styles.php'; ?>
  $[if `process.env.ICONS !== "false"`]$
  <link data-turbo-eval="false" href="https://cdn.jsdelivr.net/npm/file-icons-js@1/css/style.min.css" rel="stylesheet"></link>
  $[end]$
  <script src="https://cdn.jsdelivr.net/npm/@hotwired/turbo@8.0/dist/turbo.es2017-umd.min.js"></script>
  <?php require __DIR__ . '/partials/head-theme.php'; ?>
</head>

<body class="d-flex flex-column min-vh-100">
  <?php require __DIR__ . '/partials/content.php'; ?>
  <?php require __DIR__ . '/partials/scripts.php'; ?>
</body>

</html>
