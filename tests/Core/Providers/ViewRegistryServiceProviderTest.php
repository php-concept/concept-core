<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\View\Registries\ViewRegistry;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Providers\ViewRegistryServiceProvider;
use League\Container\Container;
use PHPUnit\Framework\TestCase;

final class ViewRegistryServiceProviderTest extends TestCase
{
    public function testProvidesViewRegistry(): void
    {
        $provider = new ViewRegistryServiceProvider();

        self::assertTrue($provider->provides(ViewRegistry::class));
        self::assertFalse($provider->provides('view.registry.unknown'));
    }

    public function testRegisterBuildsViewRegistryFromConfig(): void
    {
        $container = new Container();
        $container->add(ConfigInterface::class, new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed
            {
                return match ($key) {
                    ConfigKey::VIEW_PATHS => ['ui' => 'resources/views/ui'],
                    ConfigKey::VIEW_EXTENSIONS => ['App\\View\\ContextExtension'],
                    ConfigKey::VIEW_CONTEXTS => ['/admin' => 'admin'],
                    default => $default,
                };
            }
            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string { return $default; }
            public function getInt(string $key, int $default = 0): int { return $default; }
            public function getBool(string $key, bool $default = false): bool { return $default; }
        })->setShared(true);

        $provider = new ViewRegistryServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        /** @var ViewRegistry $viewRegistry */
        $viewRegistry = $container->get(ViewRegistry::class);

        self::assertSame(['ui' => 'resources/views/ui'], $viewRegistry->paths()->all());
        self::assertSame(['App\\View\\ContextExtension'], $viewRegistry->extensions()->all());
        self::assertSame(['/admin' => 'admin'], $viewRegistry->contexts()->all());
    }
}
