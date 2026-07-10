<?php

final class BatchDownloadService
{
  public function __construct(
    private PathPolicy $paths,
    private FileRepository $files,
  ) {}

  public function download(array $urls): never
  {
    try {
      $files = $this->collect($urls);
      $this->validateSizes($files);
    } catch (InvalidArgumentException|RuntimeException $exception) {
      http_response_code(400);
      header('Content-Type: text/plain; charset=utf-8');
      echo 'Batch download error: ' . $exception->getMessage();
      die();
    }

    $[if `process.env.DOWNLOAD_COUNTER === "true"`]$
    $this->incrementCounters($files);
    $[end]$

    $streamingStarted = false;
    try {
      @ini_set('zlib.output_compression', 'Off');
      @ini_set('implicit_flush', '1');
      @ini_set('output_buffering', 'Off');
      @ini_set('display_errors', '0');
      @ini_set('html_errors', '0');
      @set_time_limit(0);
      while (ob_get_level() > 0) @ob_end_clean();
      @ob_implicit_flush(true);

      header('X-Accel-Buffering: no');
      header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
      header('Pragma: no-cache');
      header('Expires: 0');
      header('Content-Encoding: identity');

      $compression = strtoupper('${{`process.env.BATCH_ZIP_COMPRESS_ALGO`}}$') === 'STORE'
        ? \ZipStream\CompressionMethod::STORE
        : \ZipStream\CompressionMethod::DEFLATE;
      $zip = new \ZipStream\ZipStream(
        outputName: bin2hex(random_bytes(8)) . '.zip',
        sendHttpHeaders: true,
        contentType: 'application/zip',
        defaultCompressionMethod: $compression,
        defaultEnableZeroHeader: true,
        enableZip64: true,
        flushOutput: true,
      );
      $streamingStarted = true;

      foreach ($files as $file) {
        $modified = $file['mtime'] === null ? null : (new DateTimeImmutable())->setTimestamp($file['mtime']);
        $zip->addFileFromPath(
          fileName: $file['archive_name'],
          path: $file['full_path'],
          lastModificationDateTime: $modified,
          exactSize: $file['size'],
          enableZeroHeader: true,
        );
      }
      $zip->finish();
      die();
    } catch (Throwable $exception) {
      if (!$streamingStarted && !headers_sent()) {
        http_response_code(500);
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Batch download error: ' . $exception->getMessage();
      }
      die();
    }
  }

  private function collect(array $inputUrls): array
  {
    $stack = array_reverse(array_values($inputUrls));
    $visitedDirectories = [];
    $collected = [];
    $maxFiles = max(1, (int) '${{`process.env.BATCH_MAX_FILES ?? '10000'`}}$');

    while ($stack !== []) {
      $url = array_pop($stack);
      if (!is_string($url)) throw new InvalidArgumentException('Batch paths must be strings');
      $path = $this->files->available($url);
      if ($path === false) throw new InvalidArgumentException("Unavailable path: {$url}");

      if (is_dir($path)) {
        if (isset($visitedDirectories[$path])) continue;
        $visitedDirectories[$path] = true;
        $children = array_diff(scandir($path) ?: [], ['.', '..']);
        foreach (array_reverse($children) as $child) $stack[] = $this->paths->toUrl($path . '/' . $child);
        continue;
      }

      if (!is_file($path)) continue;
      if (count($collected) >= $maxFiles) throw new RuntimeException("Batch exceeds {$maxFiles} files");
      $size = filesize($path);
      if ($size === false) throw new RuntimeException('Cannot read file size for ' . $this->paths->toUrl($path));
      $urlPath = $this->paths->toUrl($path);
      $collected[] = [
        'url' => $urlPath,
        'archive_name' => ltrim($urlPath, '/'),
        'full_path' => $path,
        'size' => (int) $size,
        'mtime' => @filemtime($path) ?: null,
      ];
    }

    if ($collected === []) throw new InvalidArgumentException('No downloadable files selected');
    return $collected;
  }

  private function validateSizes(array $files): void
  {
    $total = 0;
    $maxFileBytes = 1024 * 1024 * (int) '${{`process.env.BATCH_MAX_FILE_SIZE`}}$';
    $maxTotalBytes = 1024 * 1024 * (int) '${{`process.env.BATCH_MAX_TOTAL_SIZE`}}$';
    foreach ($files as $file) {
      if ($file['size'] > $maxFileBytes) throw new RuntimeException($file['url'] . ' exceeds the per-file size limit');
      $total += $file['size'];
      if ($total > $maxTotalBytes) throw new RuntimeException('Batch exceeds the total size limit');
    }
  }

  private function incrementCounters(array $files): void
  {
    try {
      $redis = new Redis();
      $redis->connect('127.0.0.1', 6379, 0.25);
      $urls = array_column($files, 'url');
      $current = $redis->mget($urls);
      $updates = [];
      foreach ($urls as $index => $url) $updates[$url] = ((int) ($current[$index] ?? 0)) + 1;
      if ($updates !== []) $redis->mset($updates);
    } catch (Throwable) {
      // Downloading remains available when counters are temporarily unavailable.
    }
  }
}

function downloadBatch(array $urls): never
{
  global $batchDownloadService;
  $batchDownloadService->download($urls);
}
