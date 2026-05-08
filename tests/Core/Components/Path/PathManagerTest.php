<?php declare(strict_types=1);

namespace Tests\Core;

use Concept\Core\Components\Path\PathManager;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class PathManagerTest extends TestCase
{
    public function testRootReturnsPathFromProjectRoot(): void
    {
        $manager = new PathManager('/var/www/project', []);

        self::assertSame('/var/www/project/config/app.php', $manager->root('/config/app.php'));
    }

    public function testGetReturnsMappedPathWithSubpath(): void
    {
        $manager = new PathManager('/var/www/project', [
            PathManager::LOGS_DIR => 'storage/logs',
        ]);

        self::assertSame('/var/www/project/storage/logs/debug.log', $manager->get(PathManager::LOGS_DIR, 'debug.log'));
    }

    public function testGetThrowsOnUnknownKey(): void
    {
        $manager = new PathManager('/var/www/project', []);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid path key');

        $manager->get('unknown');
    }
}
