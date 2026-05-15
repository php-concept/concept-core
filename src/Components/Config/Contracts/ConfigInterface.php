<?php declare(strict_types=1);

namespace Concept\Core\Components\Config\Contracts;

interface ConfigInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function has(string $key): bool;

    /**
     * @return array<mixed, mixed>
     */
    public function all(): array;

    public function getString(string $key, string $default = ''): string;

    public function getInt(string $key, int $default = 0): int;

    public function getBool(string $key, bool $default = false): bool;
}
