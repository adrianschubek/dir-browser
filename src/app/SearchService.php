<?php

final class SearchValidationException extends InvalidArgumentException {}

final class SearchService
{
  public function __construct(
    private PathPolicy $paths,
    private FileRepository $files,
    private AccessControl $access,
  ) {}

  public function search(string $query, string $rootFolder, string $engine): array
  {
    $query = trim($query);
    $maxLength = max(1, (int) '${{`process.env.SEARCH_MAX_QUERY_LENGTH ?? '256'`}}$');
    if ($query === '') throw new SearchValidationException('Empty search query');
    if (mb_strlen($query) > $maxLength) throw new SearchValidationException("Search query exceeds {$maxLength} characters");

    $iterator = match ($engine) {
      's' => $this->simpleIterator($query, $rootFolder),
      'r' => $this->regexIterator($query, $rootFolder),
      'g' => $this->globIterator($query, $rootFolder),
      default => throw new SearchValidationException('Invalid search engine'),
    };

    $results = [];
    $limit = max(1, (int) '${{`process.env.SEARCH_MAX_RESULTS`}}$');
    foreach ($iterator as $path) {
      $resolved = realpath((string) $path);
      if ($resolved === false || !$this->paths->containsWithin($resolved, $rootFolder)) continue;

      $available = $this->files->available($this->paths->toUrl($resolved));
      if ($available === false) continue;

      $isDirectory = is_dir($available);
      $authRequired = false;
      $authLocked = false;
      if ($isDirectory) {
        $status = $this->access->statusForPath($available);
        $authRequired = $status['requires_password'];
        $authLocked = $authRequired && !$status['authorized'];
      }

      $relativeName = ltrim(substr($available, strlen(rtrim($rootFolder, DIRECTORY_SEPARATOR))), DIRECTORY_SEPARATOR);
      $results[] = [
        'url' => $this->paths->toUrl($available),
        'href' => $this->paths->encodeUrlPath($this->paths->toUrl($available)),
        'name' => safe_utf8($relativeName),
        'is_dir' => $isDirectory,
        'auth_required' => $authRequired,
        'auth_locked' => $authLocked,
      ];

      if (count($results) >= $limit) break;
    }

    return [
      'results' => $results,
      'total' => count($results),
      'truncated' => count($results) >= $limit,
      'base_folder' => $this->paths->toUrl($rootFolder),
    ];
  }

  private function recursiveIterator(string $rootFolder): RecursiveIteratorIterator
  {
    $directories = new RecursiveDirectoryIterator($rootFolder, RecursiveDirectoryIterator::SKIP_DOTS);
    $iterator = new RecursiveIteratorIterator($directories);
    $iterator->setMaxDepth(max(0, (int) '${{`process.env.SEARCH_MAX_DEPTH`}}$'));
    return $iterator;
  }

  private function simpleIterator(string $query, string $rootFolder): iterable
  {
    return new CallbackFilterIterator($this->recursiveIterator($rootFolder), static function ($current) use ($query): bool {
      return str_contains(mb_strtolower($current->getFilename()), mb_strtolower($query));
    });
  }

  private function regexIterator(string $query, string $rootFolder): iterable
  {
    $pattern = '~' . str_replace('~', '\\~', $query) . '~iu';
    if (@preg_match($pattern, '') === false) throw new SearchValidationException('Invalid regular expression');
    return new RegexIterator($this->recursiveIterator($rootFolder), $pattern, RecursiveRegexIterator::MATCH);
  }

  private function globIterator(string $query, string $rootFolder): iterable
  {
    if (str_contains($query, "\0") || str_contains($query, '/') || str_contains($query, '\\') || str_contains($query, '..')) {
      throw new SearchValidationException('Glob searches must be a filename pattern within the current folder');
    }
    return new GlobIterator(rtrim($rootFolder, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $query, FilesystemIterator::SKIP_DOTS);
  }
}

function globalsearch(string $query, string $rootFolder, string $engine): array
{
  global $searchService;
  return $searchService->search($query, $rootFolder, $engine);
}
