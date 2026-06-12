<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Services\Config\Contracts\ConfigInterface;
use Concept\Core\Services\Database\Contracts\DatabaseInterface;
use Concept\Core\Services\Logger\QueryLogger;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Foundation\PathManager;
use Concept\Core\Foundation\PathName;
use Concept\Core\Providers\Database\DatabaseServiceProvider;
use Illuminate\Database\Capsule\Manager as CapsuleManager;
use Illuminate\Database\Migrations\Migrator;
use League\Container\Container;
use PHPUnit\Framework\TestCase;

final class DatabaseServiceProviderTest extends TestCase
{
    private string $tmpRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpRoot = sys_get_temp_dir() . '/db-provider-' . bin2hex(random_bytes(6));
        mkdir($this->tmpRoot . '/storage/logs', 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tmpRoot . '/storage/logs/query*.log') ?: [] as $logFile) {
            unlink($logFile);
        }
        if (is_dir($this->tmpRoot . '/storage/logs')) {
            rmdir($this->tmpRoot . '/storage/logs');
        }
        if (is_dir($this->tmpRoot . '/storage')) {
            rmdir($this->tmpRoot . '/storage');
        }
        if (is_dir($this->tmpRoot)) {
            rmdir($this->tmpRoot);
        }
        parent::tearDown();
    }

    public function testProvidesExpectedServices(): void
    {
        $provider = new DatabaseServiceProvider();

        self::assertTrue($provider->provides(CapsuleManager::class));
        self::assertTrue($provider->provides(DatabaseInterface::class));
        self::assertTrue($provider->provides(QueryLogger::class));
        self::assertTrue($provider->provides(Migrator::class));
        self::assertFalse($provider->provides('db.unknown'));
    }

    public function testBootAndRegisterBindDatabaseServices(): void
    {
        $container = $this->createContainer(logQuery: false);

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

    public function testBootDoesNotWriteQueriesWhenDisabledEvenInDebug(): void
    {
        $container = $this->createContainer(logQuery: false, appDebug: true);

        $provider = new DatabaseServiceProvider();
        $provider->setContainer($container);
        $provider->boot();
        $provider->register();

        /** @var CapsuleManager $capsule */
        $capsule = $container->get(CapsuleManager::class);
        $capsule->getConnection()->select('select 1');

        self::assertEmpty(glob($this->tmpRoot . '/storage/logs/query*.log') ?: []);
    }

    public function testBootWritesQueriesToQueryLogWhenEnabled(): void
    {
        $container = $this->createContainer(logQuery: true);

        $provider = new DatabaseServiceProvider();
        $provider->setContainer($container);
        $provider->boot();
        $provider->register();

        /** @var CapsuleManager $capsule */
        $capsule = $container->get(CapsuleManager::class);
        $capsule->getConnection()->select('select 1');

        $logFiles = glob($this->tmpRoot . '/storage/logs/query*.log') ?: [];
        self::assertNotEmpty($logFiles);
        self::assertStringContainsString('SQL: select 1', (string) file_get_contents($logFiles[0]));
    }

    private function createContainer(bool $logQuery, bool $appDebug = false): Container
    {
        $container = new Container();
        $container->add(PathManager::class, new PathManager($this->tmpRoot, [
            PathName::LOGS => 'storage/logs',
        ]))->setShared(true);
        $container->add(ConfigInterface::class, new class ($logQuery, $appDebug) implements ConfigInterface {
            public function __construct(
                private readonly bool $logQuery,
                private readonly bool $appDebug = false,
            ) {}

            public function get(string $key, mixed $default = null): mixed { return $default; }
            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string
            {
                return match ($key) {
                    ConfigKey::DB_DRIVER => 'sqlite',
                    ConfigKey::DB_DATABASE => ':memory:',
                    default => $default,
                };
            }
            public function getInt(string $key, int $default = 0): int
            {
                return $key === ConfigKey::LOG_MAX_FILES ? 7 : $default;
            }
            public function getBool(string $key, bool $default = false): bool
            {
                return match ($key) {
                    ConfigKey::LOG_DB_QUERIES => $this->logQuery,
                    ConfigKey::APP_DEBUG => $this->appDebug,
                    default => $default,
                };
            }
        })->setShared(true);

        return $container;
    }
}
