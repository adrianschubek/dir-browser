<?php

final class MetadataRepository
{
  /** @var array<string, object|null> */
  private array $cache = [];

  public function forPath(string $path): ?object
  {
    if (array_key_exists($path, $this->cache)) return $this->cache[$path];

    $metadataPath = $path . '.dbmeta.json';
    if (!is_file($metadataPath)) return $this->cache[$path] = null;

    $raw = @file_get_contents($metadataPath);
    if ($raw === false) return $this->cache[$path] = null;

    $decoded = json_decode($raw);
    return $this->cache[$path] = is_object($decoded) ? $decoded : null;
  }

  public function isInternalMetadataPath(string $path): bool
  {
    return str_contains(basename($path), '.dbmeta.');
  }

  public function isHidden(?object $metadata): bool
  {
    return isset($metadata->hidden) && $metadata->hidden === true;
  }

  public function requiresHash(?object $metadata): bool
  {
    return isset($metadata->hash_required) && $metadata->hash_required === true;
  }

  public function escapedDescription(?object $metadata): ?string
  {
    if (!isset($metadata->description) || !is_string($metadata->description)) return null;
    return htmlspecialchars($metadata->description, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
  }
}
