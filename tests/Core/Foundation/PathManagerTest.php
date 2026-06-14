<?php declare(strict_types=1);

namespace Tests\Core\Foundation;

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

    public function testGetKeyValueReturnsMappedSegment(): void
    {
        $manager = new PathManager('/var/www/project', [
            PathName::PUBLIC => 'public',
        ]);

        self::assertSame('public', $manager->getKeyValue(PathName::PUBLIC));
        self::assertNull($manager->getKeyValue(PathName::CACHE));
    }

    public function testGetRelativeReturnsProjectRelativeMappedPath(): void
    {
        $manager = new PathManager('/var/www/project', [
            PathName::PUBLIC => 'public',
        ]);

        self::assertSame('public', $manager->getRelative(PathName::PUBLIC));
        self::assertSame(
            'public/components/auth-admin/js/admin-tokens.js',
            $manager->getRelative(PathName::PUBLIC, 'components/auth-admin/js/admin-tokens.js')
        );
        self::assertSame(
            $manager->getRelative(PathName::PUBLIC, 'components/auth-admin/js/admin-tokens.js'),
            $manager->toRelative($manager->get(PathName::PUBLIC, 'components/auth-admin/js/admin-tokens.js'))
        );
    }

    public function testGetRelativeThrowsOnUnknownKey(): void
    {
        $manager = new PathManager('/var/www/project', []);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Invalid path key');

        $manager->getRelative(PathName::PUBLIC, 'assets/app.js');
    }

    public function testToRelativeConvertsAbsolutePathInsideProject(): void
    {
        $root = sys_get_temp_dir() . '/concept-path-manager-' . uniqid('', true);
        mkdir($root . '/src/Components/AuthAdmin/Views', 0777, true);

        $manager = new PathManager($root, []);
        $absolute = $root . '/src/Components/AuthAdmin/Views';

        self::assertSame('src/Components/AuthAdmin/Views', $manager->toRelative($absolute));

        rmdir($root . '/src/Components/AuthAdmin/Views');
        rmdir($root . '/src/Components/AuthAdmin');
        rmdir($root . '/src/Components');
        rmdir($root . '/src');
        rmdir($root);
    }

    public function testToRelativeReturnsAlreadyRelativePath(): void
    {
        $manager = new PathManager('/var/www/project', []);

        self::assertSame(
            'src/Components/AuthAdmin/Views',
            $manager->toRelative('src/Components/AuthAdmin/Views')
        );
    }

    public function testToRelativeThrowsWhenPathIsOutsideProjectRoot(): void
    {
        $manager = new PathManager('/var/www/project', []);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Path "/etc/passwd" is outside the project root.');

        $manager->toRelative('/etc/passwd');
    }
}
