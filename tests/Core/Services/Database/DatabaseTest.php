<?php declare(strict_types=1);

namespace Tests\Core\Services\Database;

use Concept\Core\Services\Database\Database;
use Illuminate\Database\Capsule\Manager as CapsuleManager;
use PHPUnit\Framework\TestCase;

final class DatabaseTest extends TestCase
{
    public function testCapsuleReturnsInjectedInstance(): void
    {
        $capsule = new CapsuleManager();
        $database = new Database($capsule);

        self::assertSame($capsule, $database->capsule());
    }
}
