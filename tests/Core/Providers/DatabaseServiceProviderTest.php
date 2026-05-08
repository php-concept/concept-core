<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Database\Contracts\DatabaseInterface;
use Concept\Core\Components\Logger\Contracts\LoggerInterface;
use Concept\Core\Providers\DatabaseServiceProvider;
use Illuminate\Database\Capsule\Manager as CapsuleManager;
use Illuminate\Database\Migrations\Migrator;
use League\Container\Container;
use PHPUnit\Framework\TestCase;

final class DatabaseServiceProviderTest extends TestCase
{
    public function testProvidesExpectedServices(): void
    {
        $provider = new DatabaseServiceProvider();

        self::assertTrue($provider->provides(CapsuleManager::class));
        self::assertTrue($provider->provides(DatabaseInterface::class));
        self::assertTrue($provider->provides(Migrator::class));
        self::assertFalse($provider->provides('db.unknown'));
    }

    public function testBootAndRegisterBindDatabaseServices(): void
    {
        $container = new Container();
        $container->add(ConfigInterface::class, new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed { return $default; }
            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string
            {
                return match ($key) {
                    'db.driver' => 'sqlite',
                    'db.database' => ':memory:',
                    default => $default,
                };
            }
            public function getInt(string $key, int $default = 0): int { return $default; }
            public function getBool(string $key, bool $default = false): bool
            {
                return $key === 'log.query' ? false : $default;
            }
        })->setShared(true);

        $provider = new DatabaseServiceProvider();
        $provider->setContainer($container);
        $provider->boot();
        $provider->register();

        $capsule = $container->get(CapsuleManager::class);
        self::assertInstanceOf(CapsuleManager::class, $capsule);

        $db = $container->get(DatabaseInterface::class);
        self::assertSame($capsule, $db->capsule());

        $migrator = $container->get(Migrator::class);
        self::assertInstanceOf(Migrator::class, $migrator);
    }

    public function testBootEnablesQueryLoggingThroughContainerLogger(): void
    {
        $container = new Container();
        $container->add(ConfigInterface::class, new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed { return $default; }
            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string
            {
                return match ($key) {
                    'db.driver' => 'sqlite',
                    'db.database' => ':memory:',
                    default => $default,
                };
            }
            public function getInt(string $key, int $default = 0): int { return $default; }
            public function getBool(string $key, bool $default = false): bool
            {
                return $key === 'log.query' ? true : $default;
            }
        })->setShared(true);

        $loggedSql = null;
        $loggedContext = null;
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::atLeastOnce())
            ->method('debug')
            ->willReturnCallback(function (string $message, array $context) use (&$loggedSql, &$loggedContext): void {
                $loggedSql = $message;
                $loggedContext = $context;
            });
        $container->add(LoggerInterface::class, $logger, true);

        $provider = new DatabaseServiceProvider();
        $provider->setContainer($container);
        $provider->boot();
        $provider->register();

        /** @var CapsuleManager $capsule */
        $capsule = $container->get(CapsuleManager::class);
        $capsule->getConnection()->select('select 1');

        self::assertNotNull($loggedSql);
        self::assertStringStartsWith('SQL: select 1', (string) $loggedSql);
        self::assertArrayHasKey('bindings', (array) $loggedContext);
        self::assertArrayHasKey('time', (array) $loggedContext);
    }
}
