<?php declare(strict_types=1);

namespace Concept\Core\Components\Path;

use InvalidArgumentException;

/**
 * PathManager handles all directory resolution logic
 */
class PathManager
{
    public const string BOOTSTRAP_DIR = 'bootstrap';
    public const string SRC_DIR = 'src';
    public const string CONFIG_DIR = 'config';
    public const string DATABASE_DIR = 'database';
    public const string MIGRATIONS_DIR = 'migrations';
    public const string SEEDERS_DIR = 'seeders';
    public const string RESOURCES_DIR = 'resources';
    public const string VIEWS_DIR = 'views';
    public const string STORAGE_DIR = 'storage';
    public const string LOGS_DIR = 'logs';
    public const string CACHE_DIR = 'cache';
    public const string ERRORS_FALLBACK_VIEWS_DIR = 'errors_fallback_views';

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