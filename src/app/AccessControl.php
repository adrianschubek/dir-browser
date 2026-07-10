<?php

final class AuthSessionStore
{
  private const COOKIE_NAME = 'dir_browser_session';
  private ?Redis $redis = null;

  public function explicitKey(): ?string
  {
    foreach ([
      $_SERVER['HTTP_X_KEY'] ?? null,
      $_POST['key'] ?? null,
      $_GET['key'] ?? null,
    ] as $candidate) {
      if (is_string($candidate) && $candidate !== '') return $candidate;
    }
    return null;
  }

  public function isGranted(string $credentialId): bool
  {
    $token = $this->tokenFromCookie();
    if ($token === null || ($redis = $this->redis()) === null) return false;

    try {
      return (bool) $redis->sIsMember($this->redisKey($token), $credentialId);
    } catch (Throwable) {
      return false;
    }
  }

  public function grant(string $credentialId): void
  {
    $redis = $this->redis();
    if ($redis === null) return;

    $token = $this->tokenFromCookie() ?? bin2hex(random_bytes(32));
    $lifetime = max(60, (int) '${{`process.env.AUTH_COOKIE_LIFETIME`}}$');

    try {
      $key = $this->redisKey($token);
      $redis->sAdd($key, $credentialId);
      $redis->expire($key, $lifetime);
    } catch (Throwable) {
      return;
    }

    setcookie(self::COOKIE_NAME, $token, $this->cookieOptions(time() + $lifetime));
  }

  public function clear(): void
  {
    $token = $this->tokenFromCookie();
    if ($token !== null && ($redis = $this->redis()) !== null) {
      try {
        $redis->del($this->redisKey($token));
      } catch (Throwable) {
        // Authentication logout should still clear the browser cookie.
      }
    }

    setcookie(self::COOKIE_NAME, '', $this->cookieOptions(time() - 3600));
    // Remove the legacy cookie that contained the raw folder password.
    setcookie('dir_browser_key', '', $this->cookieOptions(time() - 3600));
    unset($_COOKIE[self::COOKIE_NAME], $_COOKIE['dir_browser_key']);
  }

  private function redis(): ?Redis
  {
    if ($this->redis !== null) return $this->redis;
    if (!class_exists('Redis')) return null;

    try {
      $redis = new Redis();
      $redis->connect('127.0.0.1', 6379, 0.25);
      return $this->redis = $redis;
    } catch (Throwable) {
      return null;
    }
  }

  private function tokenFromCookie(): ?string
  {
    $token = $_COOKIE[self::COOKIE_NAME] ?? null;
    return is_string($token) && preg_match('/\A[a-f0-9]{64}\z/', $token) === 1 ? $token : null;
  }

  private function redisKey(string $token): string
  {
    return 'dir-browser:auth-session:' . $token;
  }

  private function cookieOptions(int $expires): array
  {
    $path = '${{`process.env.BASE_PATH ?? ''`}}$' ?: '/';
    $secureMode = strtolower((string) '${{`process.env.AUTH_COOKIE_SECURE ?? 'auto'`}}$');
    $forwardedProto = strtolower(trim(explode(',', (string) ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? ''))[0]));
    $requestIsHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || $forwardedProto === 'https';
    $secure = $secureMode === 'true' || ($secureMode === 'auto' && $requestIsHttps);

    return [
      'expires' => $expires,
      'path' => $path,
      'secure' => $secure,
      'httponly' => true,
      'samesite' => 'Lax',
    ];
  }
}

final class AccessControl
{
  /** @var array<string, array|false> */
  private array $configCache = [];
  /** @var array<string, array> */
  private array $effectiveCache = [];

  public function __construct(
    private PathPolicy $paths,
    private AuthSessionStore $sessions,
  ) {}

  public function readConfig(string $directory): ?array
  {
    if (array_key_exists($directory, $this->configCache)) {
      $cached = $this->configCache[$directory];
      return $cached === false ? null : $cached;
    }

    $path = rtrim($directory, DIRECTORY_SEPARATOR) . '/.access.json';
    if (!is_file($path) || ($raw = @file_get_contents($path)) === false) {
      $this->configCache[$directory] = false;
      return null;
    }

    $json = json_decode($raw, true);
    if (!is_array($json)) {
      $this->configCache[$directory] = false;
      return null;
    }

    $config = [];
    foreach (['password_hash', 'password_raw'] as $key) {
      if (isset($json[$key]) && is_string($json[$key]) && $json[$key] !== '') $config[$key] = $json[$key];
    }
    foreach (['hidden', 'inherit'] as $key) {
      if (array_key_exists($key, $json)) $config[$key] = (bool) $json[$key];
    }

    return $this->configCache[$directory] = $config;
  }

  public function effectiveForDirectory(string $directory): array
  {
    if (isset($this->effectiveCache[$directory])) return $this->effectiveCache[$directory];

    $chain = [];
    $cursor = $directory;
    while ($this->paths->contains($cursor)) {
      if (($config = $this->readConfig($cursor)) !== null) $chain[] = ['dir' => $cursor, 'config' => $config];
      if ($cursor === $this->paths->root()) break;
      $parent = dirname($cursor);
      if ($parent === $cursor) break;
      $cursor = $parent;
    }

    $effective = ['hidden' => false, 'requires_password' => false, 'credential_id' => null];
    foreach (array_reverse($chain) as $entry) {
      $config = $entry['config'];
      if ($entry['dir'] !== $directory && ($config['inherit'] ?? true) !== true) continue;

      if (array_key_exists('hidden', $config)) $effective['hidden'] = $config['hidden'];
      if (isset($config['password_hash'])) {
        $effective['password_hash'] = $config['password_hash'];
        unset($effective['password_raw']);
      } elseif (isset($config['password_raw'])) {
        $effective['password_raw'] = $config['password_raw'];
        unset($effective['password_hash']);
      }
    }

    $effective['requires_password'] = isset($effective['password_hash']) || isset($effective['password_raw']);
    if ($effective['requires_password']) {
      $material = isset($effective['password_hash'])
        ? 'hash:' . $effective['password_hash']
        : 'raw:' . $effective['password_raw'];
      $secret = getenv('AUTH_SESSION_SECRET');
      if ($secret === false || $secret === '') {
        $secret = @file_get_contents(dirname(__DIR__) . '/tmp/auth-session-secret') ?: 'dir-browser-session';
      }
      $effective['credential_id'] = hash_hmac('sha256', $material, $secret);
    }

    return $this->effectiveCache[$directory] = $effective;
  }

  public function statusForPath(string $localPath, bool $includeProtected = false): array
  {
    $directory = is_dir($localPath) ? $localPath : dirname($localPath);
    $effective = $this->effectiveForDirectory($directory);
    $authorized = $this->isAuthorized($effective);
    if ($includeProtected) $authorized = true;

    return [
      'hidden' => (bool) $effective['hidden'],
      'requires_password' => (bool) $effective['requires_password'],
      'authorized' => $authorized,
      'credential_id' => $effective['credential_id'],
    ];
  }

  public function grant(array $status): void
  {
    if (is_string($status['credential_id'] ?? null)) $this->sessions->grant($status['credential_id']);
  }

  public function clearSession(): void
  {
    $this->sessions->clear();
  }

  public function explicitKey(): ?string
  {
    return $this->sessions->explicitKey();
  }

  private function isAuthorized(array $effective): bool
  {
    if (!$effective['requires_password']) return true;

    $key = $this->sessions->explicitKey();
    if ($key !== null) {
      if (isset($effective['password_hash']) && password_verify($key, $effective['password_hash'])) return true;
      if (isset($effective['password_raw']) && hash_equals((string) $effective['password_raw'], $key)) return true;
    }

    return is_string($effective['credential_id']) && $this->sessions->isGranted($effective['credential_id']);
  }
}

function request_url_auth_key(): ?string
{
  $auth = $_GET['auth'] ?? null;
  return is_string($auth) && $auth !== '' ? $auth : null;
}

function with_auth_query_param(string $url): string
{
  $auth = request_url_auth_key();
  if ($auth === null) return $url;

  $fragment = '';
  if (($hashPosition = strpos($url, '#')) !== false) {
    $fragment = substr($url, $hashPosition);
    $url = substr($url, 0, $hashPosition);
  }

  [$base, $queryString] = array_pad(explode('?', $url, 2), 2, '');
  parse_str($queryString, $query);
  $query['auth'] ??= $auth;
  $encoded = http_build_query($query);
  return $base . ($encoded !== '' ? '?' . $encoded : '') . $fragment;
}

function redirect_without_key_param(): never
{
  $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? '/';
  parse_str(parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY) ?? '', $query);
  unset($query['key']);
  $queryString = http_build_query($query);
  http_response_code(303);
  header('Location: ' . $path . ($queryString !== '' ? '?' . $queryString : ''));
  die();
}

function request_access_key(): ?string
{
  global $accessControl;
  return $accessControl->explicitKey();
}

function access_status_for_local_path(string $localPath, bool $includeProtected = false): array
{
  global $accessControl;
  return $accessControl->statusForPath($localPath, $includeProtected);
}

function delete_auth_cookie(): void
{
  global $accessControl;
  $accessControl->clearSession();
}

function is_access_config_file_path(string $path): bool
{
  global $pathPolicy;
  return $pathPolicy->isAccessConfig($path);
}
