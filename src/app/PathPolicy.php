<?php

final class PublicRootUnavailableException extends RuntimeException {}

/**
 * Filesystem boundary and URL-path policy.
 *
 * The configured root is a logical path (and may itself be a symlink). The
 * resolved root is the canonical security boundary used for every filesystem
 * operation. Dotfiles remain public unless an ignore, metadata, or access rule
 * hides their browser-visible URL.
 */
final class PathPolicy
{
  private string $contentMount;
  private string $logicalRoot;
  private string $publicRoot;

  public function __construct(string $contentMount, string $configuredRoot)
  {
    $this->contentMount = self::validateLogicalPath($contentMount, 'Content mount');
    $this->logicalRoot = self::validateLogicalPath($configuredRoot, 'PUBLIC_ROOT');

    if (!$this->isLogicalRootWithinMount()) {
      throw new PublicRootUnavailableException(
        "PUBLIC_ROOT must be the content mount or one of its descendants: {$this->contentMount}"
      );
    }

    clearstatcache(true);
    $resolvedMount = realpath($this->contentMount);
    if ($resolvedMount === false || !is_dir($resolvedMount)) {
      throw new PublicRootUnavailableException("Content mount is missing or is not a directory: {$this->contentMount}");
    }
    $resolvedMount = rtrim($resolvedMount, DIRECTORY_SEPARATOR);

    $resolvedRoot = realpath($this->logicalRoot);
    if ($resolvedRoot === false) {
      if (@lstat($this->logicalRoot) !== false || is_link($this->logicalRoot)) {
        throw new PublicRootUnavailableException(
          "PUBLIC_ROOT exists but cannot be resolved. Check its symlink target and execute permission on every ancestor directory: {$this->logicalRoot}"
        );
      }
      throw new PublicRootUnavailableException("PUBLIC_ROOT is missing: {$this->logicalRoot}");
    }
    $resolvedRoot = rtrim($resolvedRoot, DIRECTORY_SEPARATOR);

    if (!is_dir($resolvedRoot)) {
      throw new PublicRootUnavailableException("PUBLIC_ROOT is not a directory: {$this->logicalRoot}");
    }
    if (!$this->containsWithin($resolvedRoot, $resolvedMount)) {
      throw new PublicRootUnavailableException(
        "PUBLIC_ROOT resolves outside the content mount: {$this->logicalRoot} -> {$resolvedRoot}"
      );
    }
    if (!is_readable($resolvedRoot) || !is_executable($resolvedRoot)) {
      throw new PublicRootUnavailableException(
        "PUBLIC_ROOT is not readable/traversable. Directories need read permission and execute permission on every ancestor: {$this->logicalRoot}"
      );
    }

    $this->publicRoot = $resolvedRoot;
  }

  public function root(): string
  {
    return $this->publicRoot;
  }

  public function logicalRoot(): string
  {
    return $this->logicalRoot;
  }

  public function normalizeUserPath(string $userPath): string
  {
    $path = rawurldecode(parse_url($userPath, PHP_URL_PATH) ?? '');
    if ($path === '') return '/';
    return $path[0] === '/' ? $path : '/' . $path;
  }

  public function contains(string $resolvedPath): bool
  {
    return $this->containsWithin($resolvedPath, $this->publicRoot);
  }

  public function containsWithin(string $resolvedPath, string $resolvedRoot): bool
  {
    $root = rtrim($resolvedRoot, DIRECTORY_SEPARATOR);
    return $resolvedPath === $root || str_starts_with($resolvedPath, $root . DIRECTORY_SEPARATOR);
  }

  public function canonicalize(string $localPath): string|false
  {
    clearstatcache(true, $localPath);
    $resolved = realpath($localPath);
    if ($resolved === false || !$this->contains($resolved)) return false;
    return rtrim($resolved, DIRECTORY_SEPARATOR);
  }

  public function resolve(string $userPath): string|false
  {
    $normalized = $this->normalizeUserPath($userPath);
    return $this->canonicalize($this->publicRoot . $normalized);
  }

  /**
   * Convert an existing canonical path inside this root to a decoded public URL.
   */
  public function toUrl(string $resolvedPath): string
  {
    $canonical = $this->canonicalize($resolvedPath);
    if ($canonical === false || $canonical !== rtrim($resolvedPath, DIRECTORY_SEPARATOR)) {
      throw new InvalidArgumentException('Path is not a canonical path inside PUBLIC_ROOT');
    }
    $relative = substr($canonical, strlen($this->publicRoot));
    return $relative === '' ? '/' : str_replace(DIRECTORY_SEPARATOR, '/', $relative);
  }

  public function encodeUrlPath(string $urlPath): string
  {
    $leadingSlash = str_starts_with($urlPath, '/');
    $parts = array_map('rawurlencode', explode('/', trim($urlPath, '/')));
    $encoded = implode('/', $parts);
    return ($leadingSlash ? '/' : '') . $encoded;
  }

  public function isAccessConfig(string $path): bool
  {
    return basename($path) === '.access.json';
  }

  private static function validateLogicalPath(string $path, string $label): string
  {
    if ($path === '' || $path[0] !== '/') {
      throw new PublicRootUnavailableException("{$label} must be an absolute path");
    }
    if (preg_match('/[\x00-\x1F\x7F]/', $path) === 1) {
      throw new PublicRootUnavailableException("{$label} contains control characters");
    }
    if ($path !== '/' && str_ends_with($path, '/')) {
      throw new PublicRootUnavailableException("{$label} must not have a trailing slash");
    }
    if (str_contains($path, '//') || preg_match('#/(?:\.|\.\.)(?:/|$)#', $path) === 1) {
      throw new PublicRootUnavailableException("{$label} must be normalized without empty, . or .. components");
    }
    return $path;
  }

  private function isLogicalRootWithinMount(): bool
  {
    return $this->logicalRoot === $this->contentMount
      || str_starts_with($this->logicalRoot, $this->contentMount . DIRECTORY_SEPARATOR);
  }
}

function isIgnoredUrlPath(string $urlPath): bool
{
  $normalized = '/' . ltrim(str_replace(DIRECTORY_SEPARATOR, '/', $urlPath), '/');

  // Dotfiles are intentionally not hidden by default.
  $[if `process.env.IGNORE !== undefined`]$
  $ignorePatterns = explode(';', "${{`process.env.IGNORE ?? ""`}}$");
  foreach ($ignorePatterns as $pattern) {
    if ($pattern === '') continue;
    $result = @preg_match('#' . $pattern . '#im', $normalized);
    if ($result === false) {
      error_log("Skipping invalid IGNORE regular expression: {$pattern}");
      continue;
    }
    if ($result === 1) return true;
  }
  $[end]$
  return false;
}
