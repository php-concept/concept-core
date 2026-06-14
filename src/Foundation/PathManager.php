<?php declare(strict_types=1);

namespace Concept\Core\Foundation;

use InvalidArgumentException;
use RuntimeException;

/**
 * PathManager handles all directory resolution logic
 */
class PathManager
{
    private const string ERR_INVALID_PATH_KEY = 'Invalid path key';
    private const string ERR_OUTSIDE_PROJECT_ROOT = 'Path "%s" is outside the project root.';

    /**
     * @param string $rootPath
     * @param array<string> $pathMap
     */
    public function __construct(
        private readonly string $rootPath,
        private readonly array $pathMap
    ) {}

    /**
     * Resolve a path relative to the project root
     */
    public function root(string $path = ''): string
    {
        return sprintf('%s/%s', $this->rootPath, ltrim($path, '/'));
    }

    public function has(string $key): bool
    {
        return isset($this->pathMap[$key]);
    }

    public function getKeyValue(string $key): ?string
    {
        return $this->pathMap[$key] ?? null;
    }

    /**
     * Resolve a path relative to the root directory
     */
    public function get(string $key, string $subPath = ''): string
    {
        if (!isset($this->pathMap[$key])) {
            throw new InvalidArgumentException(self::ERR_INVALID_PATH_KEY);
        }

        $base = $this->pathMap[$key];
        $fullPath = rtrim($this->rootPath, '/') . '/' . ltrim($base, '/');

        return $subPath ? $fullPath . '/' . ltrim($subPath, '/') : $fullPath;
    }

    /**
     * Resolve a mapped path relative to the project root.
     */
    public function getRelative(string $key, string $subPath = ''): string
    {
        return $this->toRelative($this->get($key, $subPath));
    }

    /**
     * Convert an absolute path, or an already project-relative path, to a project-relative path.
     */
    public function toRelative(string $path): string
    {
        $path = $this->normalizePath($path);

        if ($path === '' || ($path[0] !== '/' && !preg_match('/^[A-Za-z]:/', $path))) {
            return ltrim($path, '/');
        }

        $root = rtrim($this->normalizePath($this->rootPath), '/');

        if (!str_starts_with($path, $root . '/')) {
            throw new RuntimeException(sprintf(self::ERR_OUTSIDE_PROJECT_ROOT, $path));
        }

        return substr($path, strlen($root) + 1);
    }

    private function normalizePath(string $path): string
    {
        return str_replace('\\', '/', $path);
    }
}
