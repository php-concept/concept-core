<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Caster\Caster;
use Concept\Core\Components\Caster\Contracts\CasterInterface;
use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Path\PathManager;
use Concept\Core\Providers\CastingServiceProvider;
use League\Container\Container;
use PHPUnit\Framework\TestCase;

final class CastingServiceProviderTest extends TestCase
{
    public function testProvidesCasterContract(): void
    {
        $provider = new CastingServiceProvider();

        self::assertTrue($provider->provides(CasterInterface::class));
        self::assertFalse($provider->provides(Caster::class));
    }

    public function testRegisterBindsCasterAsSharedService(): void
    {
        $tmpRoot = sys_get_temp_dir() . '/provider-casting-' . bin2hex(random_bytes(6));
        mkdir($tmpRoot . '/storage/cache', 0777, true);

        try {
            $container = new Container();
            $container->add(PathManager::class, new PathManager($tmpRoot, [
                PathManager::CACHE_DIR => 'storage/cache',
            ]))->setShared(true);

            $config = new class implements ConfigInterface {
                public function get(string $key, mixed $default = null): mixed { return $default; }
                public function set(string $key, mixed $default = null): void {}
                public function has(string $key): bool { return false; }
                public function all(): array { return []; }
                public function getString(string $key, string $default = ''): string { return $default; }
                public function getInt(string $key, int $default = 0): int { return $default; }
                public function getBool(string $key, bool $default = false): bool { return $default; }
            };

            $container->add(ConfigInterface::class, $config)->setShared(true);

            $provider = new CastingServiceProvider();
            $provider->setContainer($container);
            $provider->register();

            $casterA = $container->get(CasterInterface::class);
            $casterB = $container->get(CasterInterface::class);

            self::assertInstanceOf(Caster::class, $casterA);
            self::assertSame($casterA, $casterB);
        } finally {
            $this->removeTree($tmpRoot);
        }
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $full = $path . '/' . $item;
            if (is_dir($full)) {
                $this->removeTree($full);
            } else {
                unlink($full);
            }
        }

        rmdir($path);
    }
}
