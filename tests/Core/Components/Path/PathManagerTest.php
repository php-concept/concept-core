<?php declare(strict_types=1);

namespace Tests\Core;

use Concept\Core\Foundation\PathManager;
use Concept\Core\Foundation\PathName;
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
            PathName::LOGS => 'storage/logs',
        ]);

        self::assertSame('/var/www/project/storage/logs/debug.log', $manager->get(PathName::LOGS, 'debug.log'));
    }

    public function testGetThrowsOnUnknownKey(): void
    {
        $manager = new PathManager('/var/www/project', []);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid path key');

        $manager->get('unknown');
    }

    public function testHasReturnsWhetherPathKeyIsMapped(): void
    {
        $manager = new PathManager('/var/www/project', [
            PathName::LANG => 'resources/lang',
        ]);

        self::assertTrue($manager->has(PathName::LANG));
        self::assertFalse($manager->has(PathName::VALIDATOR_TRANSLATIONS));
    }
}
