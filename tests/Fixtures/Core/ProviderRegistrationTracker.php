<?php declare(strict_types=1);

namespace Tests\Fixtures\Core;

final class ProviderRegistrationTracker
{
    /** @var array<int, string> */
    public static array $events = [];

    public static int $loopGuard = 0;

    public static function reset(): void
    {
        self::$events = [];
        self::$loopGuard = 0;
    }
}

