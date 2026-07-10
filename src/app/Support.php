<?php

final class File
{
  public string $name;
  public string $url;
  public string $size;
  public bool $is_dir;
  public string $modified_date;
  public int $dl_count = 0;
  public ?object $meta = null;
  public bool $auth_required = false;
  public bool $auth_locked = false;

  public function __toString(): string
  {
    return $this->name;
  }
}

function human_filesize(int|float|string $bytes, int $decimals = 2): string
{
  $bytes = max(0, (float) $bytes);
  if ($bytes < 1024) return sprintf('%.0f B', $bytes);

  $units = ['B', 'KB', 'MB', 'GB', 'TB', 'PB'];
  $factor = min((int) floor(log($bytes, 1024)), count($units) - 1);
  return sprintf("%.{$decimals}f %s", $bytes / (1024 ** $factor), $units[$factor]);
}

function numsize(int|float|string $size, int $round = 2): string
{
  $size = max(0, (float) $size);
  if ($size < 1000) return (string) (int) $size;

  $units = ['', 'K', 'M', 'G', 'T'];
  $factor = min((int) floor(log($size, 1000)), count($units) - 1);
  return round($size / (1000 ** $factor), $round) . $units[$factor];
}

function safe_utf8(string $input): string
{
  $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $input);
  if ($converted !== false) return $converted;
  return preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $input) ?? '';
}

function json_response(mixed $payload, int $status = 200): never
{
  http_response_code($status);
  header('Content-Type: application/json; charset=utf-8');
  echo json_encode(
    $payload,
    JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_THROW_ON_ERROR
  );
  die();
}

function redis_client(): ?Redis
{
  if (!class_exists('Redis')) return null;
  try {
    $redis = new Redis();
    $redis->connect('127.0.0.1', 6379, 0.25);
    return $redis;
  } catch (Throwable) {
    return null;
  }
}

function hash_max_file_size_bytes(): ?int
{
  $raw = getenv('HASH_MAX_FILE_SIZE_MB');
  if ($raw === false || $raw === '' || !is_numeric($raw)) return null;
  $mb = (float) $raw;
  if ($mb <= 0) return null;
  $bytes = (int) floor($mb * 1024 * 1024);
  return $bytes > 0 ? $bytes : null;
}

function hashing_allowed_for_file(string $path): bool
{
  $maxBytes = hash_max_file_size_bytes();
  if ($maxBytes === null) return true;
  $size = @filesize($path);
  return $size !== false && $size <= $maxBytes;
}
