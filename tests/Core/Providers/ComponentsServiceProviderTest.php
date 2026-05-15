<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Component\ComponentRegistry;
use Concept\Core\Components\Component\Contracts\ComponentInterface;
use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Database\Registries\MigrationRegistry;
use Concept\Core\Components\Database\Registries\SeederRegistry;
use Concept\Core\Providers\ComponentsServiceProvider;
use League\Container\Container;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class ComponentsServiceProviderTest extends TestCase
{
    public function testProvidesComponentRegistry(): void
    {
        $provider = new ComponentsServiceProvider();

        self::assertTrue($provider->provides(ComponentRegistry::class));
        self::assertFalse($provider->provides('components.unknown'));
    }

    public function testRegisterBuildsComponentRegistryFromConfig(): void
    {
        $container = new Container();
        $container->add(StubComponent::class, new StubComponent())->setShared(true);
        $container->add(ConfigInterface::class, new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed
            {
                return $key === 'components' ? [StubComponent::class] : $default;
            }
            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string { return $default; }
            public function getInt(string $key, int $default = 0): int { return $default; }
            public function getBool(string $key, bool $default = false): bool { return $default; }
        })->setShared(true);

        $provider = new ComponentsServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        /** @var ComponentRegistry $registry */
        $registry = $container->get(ComponentRegistry::class);

        self::assertSame(['StubSeeder'], $registry->seeders());
        self::assertSame(['/stub/migrations'], $registry->migrations());
    }

    public function testRegisterComponentSeedersAndMigrationsAppendsToRegistries(): void
    {
        $container = new Container();
        $container->add(StubComponent::class, new StubComponent())->setShared(true);
        $container->add(ConfigInterface::class, new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed
            {
                return $key === 'components' ? [StubComponent::class] : $default;
            }
            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string { return $default; }
            public function getInt(string $key, int $default = 0): int { return $default; }
            public function getBool(string $key, bool $default = false): bool { return $default; }
        })->setShared(true);

        $seederRegistry = new SeederRegistry();
        $seederRegistry->append(['App\\Database\\Seeders\\DatabaseSeeder']);
        $container->add(SeederRegistry::class, $seederRegistry)->setShared(true);

        $migrationRegistry = new MigrationRegistry();
        $migrationRegistry->append(['/app/database/migrations']);
        $container->add(MigrationRegistry::class, $migrationRegistry)->setShared(true);

        $provider = new ComponentsServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        /** @var ComponentRegistry $registry */
        $registry = $container->get(ComponentRegistry::class);

        $reflection = new ReflectionClass($provider);
        $registerSeeders = $reflection->getMethod('registerComponentSeeders');
        $registerSeeders->setAccessible(true);
        $registerSeeders->invoke($provider, $registry);

        $registerMigrations = $reflection->getMethod('registerComponentMigrations');
        $registerMigrations->setAccessible(true);
        $registerMigrations->invoke($provider, $registry);

        self::assertSame(
            ['App\\Database\\Seeders\\DatabaseSeeder', 'StubSeeder'],
            $container->get(SeederRegistry::class)->all()
        );
        self::assertSame(
            ['/app/database/migrations', '/stub/migrations'],
            $container->get(MigrationRegistry::class)->all()
        );
    }
}

final class StubComponent implements ComponentInterface
{
    public function name(): string
    {
        return 'stub';
    }

    public function version(): string
    {
        return '1.0.0';
    }

    public function description(): string
    {
        return 'Stub component for tests';
    }

    public function routes(): ?string
    {
        return null;
    }

    public function providers(): array
    {
        return [];
    }

    public function twigExtensions(): array
    {
        return [];
    }

    public function twigNamespaces(): array
    {
        return [];
    }

    public function twigRouteNamespaces(): array
    {
        return [];
    }

    public function commands(): array
    {
        return [];
    }

    public function seeders(): array
    {
        return ['StubSeeder'];
    }

    public function migrations(): array
    {
        return ['/stub/migrations'];
    }
}
