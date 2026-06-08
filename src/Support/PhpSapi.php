<?php declare(strict_types=1);

namespace Concept\Core\Support;

/**
 * PHP SAPI identifiers.
 */
class PhpSapi
{
    public const string CLI = 'cli';

    public static function isCli(): bool
    {
        return PHP_SAPI === self::CLI;
    }
}
