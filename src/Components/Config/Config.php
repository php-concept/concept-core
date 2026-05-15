<?php declare(strict_types=1);

namespace Concept\Core\Components\Config;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Noodlehaus\ConfigInterface as NoodlehausConfigInterface;

class Config implements ConfigInterface
{
    public function __construct(
        private readonly NoodlehausConfigInterface $noodlehausConfig
    ) {}

    public function get(string $key, mixed $default = null): mixed
    {
        return $this->noodlehausConfig->get($key, $default);
    }

    public function has(string $key): bool
    {
        return $this->noodlehausConfig->has($key);
    }

    public function all(): array
    {
        return $this->noodlehausConfig->all();
    }

    public function getString(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);
        if(!is_scalar($value)) {
            return $default;
        }

        return (string)$value;
    }

    public function getInt(string $key, int $default = 0): int
    {
        $value = $this->get($key, $default);
        if (!is_numeric($value)) {
            return $default;
        }

        return (int)$value;
    }

    public function getBool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);
        if (is_null($value)) {
            return $default;
        }

        return filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }
}