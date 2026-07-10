<?php

final class FileRepository
{
  public function __construct(
    private PathPolicy $paths,
    private AccessControl $access,
    private MetadataRepository $metadata,
  ) {}

  public function available(string $userPath, bool $includeProtected = false): string|false
  {
    $normalized = $this->paths->normalizeUserPath($userPath);
    if ($this->paths->isAccessConfig($normalized)) return false;

    $path = $this->paths->resolve($normalized);
    if ($path === false || hidden($path)) return false;

    $status = $this->access->statusForPath($path, $includeProtected);
    if ($status['hidden']) return false;
    if (!$includeProtected && $status['requires_password'] && !$status['authorized']) return false;

    $[if `process.env.METADATA === "true"`]$
    if ($this->metadata->isInternalMetadataPath($path)) return false;
    if ($this->metadata->isHidden($this->metadata->forPath($path))) return false;
    $[end]$

    return $path;
  }
}

function available(string $userPath, bool $includeProtected = false): string|false
{
  global $fileRepository;
  return $fileRepository->available($userPath, $includeProtected);
}
