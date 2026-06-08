<?php declare(strict_types=1);

namespace Concept\Core\Foundation;

use InvalidArgumentException;

/**
 * PathManager handles all directory resolution logic
 */
class PathManager
{
    private const string ERR_INVALID_PATH_KEY = 'Invalid path key';

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
}
