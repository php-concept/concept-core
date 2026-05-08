<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Logger\Contracts\LoggerInterface;
use Concept\Core\Components\Path\PathManager;
use Concept\Core\Providers\LogServiceProvider;
use League\Container\Container;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Logger as Monolog;
use PHPUnit\Framework\TestCase;

final class LogServiceProviderTest extends TestCase
{
    private string $tmpRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpRoot = sys_get_temp_dir() . '/provider-log-' . bin2hex(random_bytes(6));
        mkdir($this->tmpRoot . '/storage/logs', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->tmpRoot);
        parent::tearDown();
    }

    public function testProvidesLoggerInterface(): void
    {
        $provider = new LogServiceProvider();

        self::assertTrue($provider->provides(LoggerInterface::class));
        self::assertFalse($provider->provides('logger.unknown'));
    }

    public function testRegisterBindsSharedLoggerWithMonologHandlers(): void
    {
        $container = new Container();
        $container->add(PathManager::class, new PathManager($this->tmpRoot, [
            PathManager::LOGS_DIR => 'storage/logs',
        ]))->setShared(true);

        $container->add(ConfigInterface::class, new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed { return $default; }
            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string
            {
                return match ($key) {
                    'log.name' => 'app-test',
                    'log.level' => 'debug',
                    default => $default,
                };
            }
            public function getInt(string $key, int $default = 0): int { return $key === 'log.max_files' ? 3 : $default; }
            public function getBool(string $key, bool $default = false): bool { return $default; }
        })->setShared(true);

        $provider = new LogServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        $loggerA = $container->get(LoggerInterface::class);
        $loggerB = $container->get(LoggerInterface::class);
        self::assertSame($loggerA, $loggerB);

        $reflection = new \ReflectionClass($loggerA);
        $monologProp = $reflection->getProperty('monolog');
        $monolog = $monologProp->getValue($loggerA);
        self::assertInstanceOf(Monolog::class, $monolog);

        $handlers = $monolog->getHandlers();
        self::assertNotEmpty($handlers);
        self::assertInstanceOf(RotatingFileHandler::class, $handlers[0]);
    }


    public function testInvalidLogLevelFallsBackToDebug(): void
    {
        $container = new Container();
        $container->add(PathManager::class, new PathManager($this->tmpRoot, [
            PathManager::LOGS_DIR => 'storage/logs',
        ]))->setShared(true);

        $container->add(ConfigInterface::class, new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed { return $default; }
            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string
            {
                return match ($key) {
                    'log.name' => 'app-test',
                    'log.level' => 'invalid-level',
                    default => $default,
                };
            }
            public function getInt(string $key, int $default = 0): int { return 1; }
            public function getBool(string $key, bool $default = false): bool { return $default; }
        })->setShared(true);

        $provider = new LogServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        $logger = $container->get(LoggerInterface::class);

        $ref = new \ReflectionClass($logger);
        $prop = $ref->getProperty('monolog');
        $monolog = $prop->getValue($logger);

        $handlers = $monolog->getHandlers();
        self::assertInstanceOf(RotatingFileHandler::class, $handlers[0]);
        self::assertSame('DEBUG', $handlers[0]->getLevel()->getName());
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
