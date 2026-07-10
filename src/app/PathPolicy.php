<?php

/**
 * Filesystem boundary and URL-path policy.
 *
 * Dotfiles are deliberately allowed. dir-browser exposes every file under the
 * configured public root unless an ignore rule, metadata, or access rule says
 * otherwise.
 */
final class PathPolicy
{
  private string $publicRoot;

  public function __construct(string $publicRoot)
  {
    $resolved = realpath($publicRoot);
    if ($resolved === false || !is_dir($resolved)) {
      throw new RuntimeException("Public folder does not exist: {$publicRoot}");
    }
    $this->publicRoot = rtrim($resolved, DIRECTORY_SEPARATOR);
  }

  public function root(): string
  {
    return $this->publicRoot;
  }

  public function normalizeUserPath(string $userPath): string
  {
    $path = rawurldecode(parse_url($userPath, PHP_URL_PATH) ?? '');
    if ($path === '') return '/';
    return $path[0] === '/' ? $path : '/' . $path;
  }

  public function contains(string $resolvedPath): bool
  {
    return $resolvedPath === $this->publicRoot
      || str_starts_with($resolvedPath, $this->publicRoot . DIRECTORY_SEPARATOR);
  }

  public function containsWithin(string $resolvedPath, string $resolvedRoot): bool
  {
    $root = rtrim($resolvedRoot, DIRECTORY_SEPARATOR);
    return $resolvedPath === $root || str_starts_with($resolvedPath, $root . DIRECTORY_SEPARATOR);
  }

  public function resolve(string $userPath): string|false
  {
    $normalized = $this->normalizeUserPath($userPath);
    $resolved = realpath($this->publicRoot . $normalized);
    if ($resolved === false || !$this->contains($resolved)) return false;
    return $resolved;
  }

  public function toUrl(string $resolvedPath): string
  {
    if (!$this->contains($resolvedPath)) {
      throw new InvalidArgumentException('Path is outside the public folder');
    }
    $relative = substr($resolvedPath, strlen($this->publicRoot));
    return $relative === '' ? '/' : $relative;
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
}

function hidden(string $path): bool
{
  // Dotfiles are intentionally not hidden by default.
  $[if `process.env.IGNORE !== undefined`]$
  $ignorePatterns = explode(';', "${{`process.env.IGNORE ?? ""`}}$");
  foreach ($ignorePatterns as $pattern) {
    if ($pattern !== '' && @preg_match('#' . $pattern . '#im', $path) === 1) return true;
  }
  $[end]$
  return false;
}
