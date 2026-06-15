<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Services\Caster\Caster;
use Concept\Core\Services\Caster\Contracts\CasterInterface;
use Concept\Core\Services\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Foundation\PathManager;
use Concept\Core\Foundation\PathName;
use Concept\Core\Providers\Support\CastingServiceProvider;
use League\Container\Container;
use League\Container\ReflectionContainer;
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
                PathName::CACHE => 'storage/cache',
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

    public function testRegisterResolvesTransformersFromConfig(): void
    {
        $tmpRoot = sys_get_temp_dir() . '/provider-casting-transformers-' . bin2hex(random_bytes(6));
        mkdir($tmpRoot . '/storage/cache', 0777, true);

        try {
            $container = new Container();
            $container->delegate(new ReflectionContainer());

            $container->add(PathManager::class, new PathManager($tmpRoot, [
                PathName::CACHE => 'storage/cache',
            ]))->setShared(true);

            $config = new class implements ConfigInterface {
                public function get(string $key, mixed $default = null): mixed
                {
                    return $key === ConfigKey::CASTER_TRANSFORMERS
                        ? [CastingProviderTestWhitespaceToNullTransformer::class]
                        : $default;
                }

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

            /** @var CasterInterface $caster */
            $caster = $container->get(CasterInterface::class);

            $dto = $caster->cast(['name' => '   '], CastingProviderTestDto::class);

            self::assertInstanceOf(CastingProviderTestDto::class, $dto);
            self::assertNull($dto->name);
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

final class CastingProviderTestDto
{
    public ?string $name;
}

final class CastingProviderTestWhitespaceToNullTransformer
{
    public function __invoke(?string $value): ?string
    {
        if ($value === null || trim($value) === '') {
            return null;
        }

        return $value;
    }
}
