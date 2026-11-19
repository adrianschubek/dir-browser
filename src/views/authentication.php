<?php
http_response_code(401);
header('Content-Type: text/html; charset=utf-8');
header('Cache-Control: no-store');

$title = getenv('TITLE');
$title = $title === false || $title === '' ? 'dir-browser' : $title;
$invalidKey = isset($_GET['auth']) && $authMode !== 'basic';
?>
<!doctype html>
<html lang="en" data-theme="light">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>Authentication required · <?= htmlspecialchars($title) ?></title>
  <?php require __DIR__ . '/partials/styles.php'; ?>
  <?php require __DIR__ . '/partials/head-theme.php'; ?>
  <style>
    .auth-page {
      display: grid;
      place-items: center;
      padding: 24px 16px;
      overflow-y: auto;
    }
    .auth-panel {
      width: min(100%, 28rem);
      box-shadow: var(--shadow-small);
    }
    .auth-banner {
      display: flex;
      min-height: 6.5rem;
      align-items: center;
      justify-content: space-between;
      gap: 1.25rem;
      padding: .85rem clamp(1.25rem, 6vw, 2rem);
      background: radial-gradient(circle, var(--line-strong) .65px, transparent 1px) right center / 12px 12px, var(--canvas);
      border-bottom: 1px solid var(--line);
      border-radius: var(--radius) var(--radius) 0 0;
    }
    .auth-brand {
      padding: .35rem .55rem;
      color: var(--ink);
      background: var(--surface);
      border: 1px solid var(--line);
      border-radius: var(--radius-small);
      font: 600 .82rem/1.4 "SFMono-Regular", Consolas, monospace;
      overflow-wrap: anywhere;
    }
    .auth-emblem {
      position: relative;
      width: 58px;
      height: 40px;
      flex: none;
      margin: 6px 5px 0 0;
      background: var(--surface);
      border: 1px solid var(--line-strong);
      border-radius: 2px var(--radius) var(--radius) var(--radius);
      box-shadow: var(--shadow-small);
    }
    .auth-emblem::before {
      content: "";
      position: absolute;
      inset: -7px auto auto -1px;
      width: 25px;
      height: 7px;
      background: var(--surface);
      border: 1px solid var(--line-strong);
      border-bottom: 0;
      border-radius: var(--radius-small) var(--radius-small) 0 0;
    }
    .auth-lock {
      position: absolute;
      right: -5px;
      bottom: -4px;
      width: 25px;
      height: 19px;
      background: var(--accent);
      border-radius: var(--radius-small);
    }
    .auth-lock::before {
      content: "";
      position: absolute;
      inset: -10px 5px auto;
      height: 12px;
      border: 2px solid var(--accent);
      border-radius: 8px 8px 0 0;
    }
    .auth-lock::after {
      content: "";
      position: absolute;
      inset: 7px 11px 5px;
      background: var(--on-accent);
      border-radius: 2px;
    }
    .auth-body { padding: clamp(1.25rem, 6vw, 2rem); }
    .auth-body h1 {
      margin: 0 0 .5rem;
      font-size: clamp(1.55rem, 5vw, 1.9rem);
      line-height: 1.15;
      letter-spacing: -.035em;
    }
    .auth-body > p {
      margin: 0;
      color: var(--muted);
      font-size: .95rem;
    }
    .auth-body > .alert { margin-top: 1rem; color: var(--danger); }
    .auth-form {
      display: grid;
      gap: .8rem;
      margin-top: 1.5rem;
    }
    .auth-form label {
      display: block;
      margin-bottom: .4rem;
      font-weight: 650;
    }
    .auth-form .form-control, .auth-form .btn { min-height: 44px; }
    .auth-form .form-control { font-size: 1rem; }
    .auth-form .alert { margin: 0; }
  </style>
</head>
<body class="auth-page">
  <main class="card auth-panel" aria-labelledby="auth-heading">
    <header class="auth-banner">
      <span class="auth-brand"><?= htmlspecialchars($title) ?></span>
      <span class="auth-emblem" aria-hidden="true"><span class="auth-lock"></span></span>
    </header>
    <div class="auth-body">
      <h1 id="auth-heading">Authentication required</h1>
      <?php if ($authMode === 'key') { ?>
        <p>Enter your authentication key to access this directory browser.</p>
        <form class="auth-form" method="get">
          <?php
          // A GET form replaces the query string, so carry the other parameters forward.
          $query = $_GET;
          unset($query['auth']);
          $queryString = http_build_query($query, '', '&', PHP_QUERY_RFC3986);
          foreach ($queryString === '' ? [] : explode('&', $queryString) as $parameter) {
            [$name, $value] = array_map('rawurldecode', explode('=', $parameter, 2));
          ?>
            <input type="hidden" name="<?= htmlspecialchars($name) ?>" value="<?= htmlspecialchars($value) ?>">
          <?php } ?>
          <div>
            <label for="auth-key">Authentication key</label>
            <input class="form-control" id="auth-key" name="auth" type="password" autocomplete="off" required
              <?= $invalidKey ? 'aria-invalid="true" aria-describedby="auth-error"' : '' ?>>
          </div>
          <?php if ($invalidKey) { ?>
            <p class="alert alert-danger" id="auth-error" role="alert">Incorrect authentication key.</p>
          <?php } ?>
          <button class="btn btn-primary" type="submit">Unlock</button>
        </form>
      <?php } elseif ($authMode === 'link') { ?>
        <p>Open the access link from your administrator. Your browser will then ask for your username and password.</p>
        <?php if ($invalidKey) { ?>
          <p class="alert alert-danger" role="alert">This access link contains an incorrect authentication key.</p>
        <?php } ?>
      <?php } else { ?>
        <p>Enter your username and password in your browser’s sign-in prompt. If you dismissed it, reload this page to try again.</p>
      <?php } ?>
    </div>
  </main>
</body>
</html>
