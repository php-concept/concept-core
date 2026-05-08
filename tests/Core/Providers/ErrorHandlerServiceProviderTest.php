<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Logger\Contracts\LoggerInterface;
use Concept\Core\Components\Path\PathManager;
use Concept\Core\Http\RequestFormat;
use Concept\Core\Providers\ErrorHandlerServiceProvider;
use League\Container\Container;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Whoops\Handler\CallbackHandler;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as Whoops;

final class ErrorHandlerServiceProviderTest extends TestCase
{
    protected function tearDown(): void
    {
        // best-effort cleanup in case test registered global handlers
        if (isset($GLOBALS['__test_whoops']) && $GLOBALS['__test_whoops'] instanceof Whoops) {
            $GLOBALS['__test_whoops']->unregister();
            unset($GLOBALS['__test_whoops']);
        }

        parent::tearDown();
    }

    public function testProvidesWhoopsService(): void
    {
        $provider = new ErrorHandlerServiceProvider();

        self::assertTrue($provider->provides(Whoops::class));
        self::assertFalse($provider->provides('whoops.unknown'));
    }

    public function testBootRegistersJsonHandlerWhenRequestExpectsJson(): void
    {
        $container = new Container();

        $whoops = new Whoops();
        $container->add(Whoops::class, $whoops, true);
        $container->add(ServerRequestInterface::class, (new ServerRequest())->withHeader('Accept', 'application/json'), true);
        $container->add(RequestFormat::class, new RequestFormat(), true);
        $container->add(ConfigInterface::class, new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed { return $default; }
            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string { return $default; }
            public function getInt(string $key, int $default = 0): int { return $default; }
            public function getBool(string $key, bool $default = false): bool { return $default; }
        }, true);

        $provider = new ErrorHandlerServiceProvider();
        $provider->setContainer($container);
        $provider->boot();

        $GLOBALS['__test_whoops'] = $whoops;

        $handlers = $whoops->getHandlers();
        self::assertNotEmpty($handlers);
        self::assertTrue($this->containsHandler($handlers, JsonResponseHandler::class));
    }

    public function testBootRegistersPlainTextHandlerForCliWhenNotJson(): void
    {
        $container = new Container();

        $whoops = new Whoops();
        $container->add(Whoops::class, $whoops, true);
        $container->add(ServerRequestInterface::class, (new ServerRequest())->withHeader('Accept', 'text/html'), true);
        $container->add(RequestFormat::class, new RequestFormat(), true);
        $container->add(ConfigInterface::class, new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed { return $default; }
            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string { return $default; }
            public function getInt(string $key, int $default = 0): int { return $default; }
            public function getBool(string $key, bool $default = false): bool { return $default; }
        }, true);

        $provider = new ErrorHandlerServiceProvider();
        $provider->setContainer($container);
        $provider->boot();

        $GLOBALS['__test_whoops'] = $whoops;

        $handlers = $whoops->getHandlers();
        self::assertTrue($this->containsHandler($handlers, PlainTextHandler::class));
    }

    public function testRegisterIsNoop(): void
    {
        $container = new Container();
        $provider = new ErrorHandlerServiceProvider();
        $provider->setContainer($container);

        $provider->register();

        self::assertFalse($container->has(Whoops::class));
    }

    public function testBootRegistersHandlersWhenRequestServicesAreMissing(): void
    {
        $container = new Container();

        $whoops = new Whoops();
        $container->add(Whoops::class, $whoops, true);
        $container->add(ConfigInterface::class, $this->makeConfig(), true);

        $provider = new ErrorHandlerServiceProvider();
        $provider->setContainer($container);
        $provider->boot();

        $GLOBALS['__test_whoops'] = $whoops;

        self::assertTrue($this->containsHandler($whoops->getHandlers(), PlainTextHandler::class));
    }

    public function testBootRegistersHandlersWhenRequestFormatIsMissing(): void
    {
        $container = new Container();

        $whoops = new Whoops();
        $container->add(Whoops::class, $whoops, true);
        $container->add(ServerRequestInterface::class, new ServerRequest(), true);
        // RequestFormat is MISSING
        $container->add(ConfigInterface::class, $this->makeConfig(), true);

        $provider = new ErrorHandlerServiceProvider();
        $provider->setContainer($container);
        $provider->boot();

        $GLOBALS['__test_whoops'] = $whoops;

        self::assertTrue($this->containsHandler($whoops->getHandlers(), PlainTextHandler::class));
    }

    public function testBootRegistersHandlersWhenServerRequestIsMissing(): void
    {
        $container = new Container();

        $whoops = new Whoops();
        $container->add(Whoops::class, $whoops, true);
        $container->add(RequestFormat::class, new RequestFormat(), true);
        // ServerRequestInterface is MISSING
        $container->add(ConfigInterface::class, $this->makeConfig(), true);

        $provider = new ErrorHandlerServiceProvider();
        $provider->setContainer($container);
        $provider->boot();

        $GLOBALS['__test_whoops'] = $whoops;

        self::assertTrue($this->containsHandler($whoops->getHandlers(), PlainTextHandler::class));
    }

    public function testBootRegistersPrettyPageHandlerWhenWebRequestAndDebugEnabled(): void
    {
        $container = new Container();

        $whoops = new Whoops();
        $container->add(Whoops::class, $whoops, true);
        $container->add(ServerRequestInterface::class, (new ServerRequest())->withHeader('Accept', 'text/html'), true);
        $container->add(RequestFormat::class, new RequestFormat(), true);
        $container->add(ConfigInterface::class, $this->makeDebugConfig(true), true);

        $provider = $this->makeWebProvider();
        $provider->setContainer($container);
        $provider->boot();

        $GLOBALS['__test_whoops'] = $whoops;

        self::assertTrue($this->containsHandler($whoops->getHandlers(), PrettyPageHandler::class));
    }

    public function testBootRegistersProductionClosureWhenWebRequestAndDebugDisabled(): void
    {
        $container = new Container();

        $whoops = new Whoops();
        $container->add(Whoops::class, $whoops, true);
        $container->add(ServerRequestInterface::class, (new ServerRequest())->withHeader('Accept', 'text/html'), true);
        $container->add(RequestFormat::class, new RequestFormat(), true);
        $container->add(ConfigInterface::class, $this->makeDebugConfig(false), true);
        $container->add(PathManager::class, new PathManager(sys_get_temp_dir(), [
            PathManager::ERRORS_FALLBACK_VIEWS_DIR => 'errors-fallback',
        ]), true);

        $provider = $this->makeWebProvider();
        $provider->setContainer($container);
        $provider->boot();

        $GLOBALS['__test_whoops'] = $whoops;

        $handlers = $whoops->getHandlers();

        self::assertCount(2, $handlers);
        self::assertInstanceOf(CallbackHandler::class, $handlers[0]);
        self::assertInstanceOf(CallbackHandler::class, $handlers[1]);
        self::assertFalse($this->containsHandler($handlers, PrettyPageHandler::class));
        self::assertFalse($this->containsHandler($handlers, PlainTextHandler::class));
        self::assertFalse($this->containsHandler($handlers, JsonResponseHandler::class));
    }

    public function testBootAppendsErrorLogClosureThatLogsThroughContainerLogger(): void
    {
        $container = new Container();

        $whoops = new Whoops();
        $container->add(Whoops::class, $whoops, true);
        $container->add(ServerRequestInterface::class, (new ServerRequest())->withHeader('Accept', 'application/json'), true);
        $container->add(RequestFormat::class, new RequestFormat(), true);
        $container->add(ConfigInterface::class, $this->makeConfig(), true);

        $loggedException = null;
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())
            ->method('exception')
            ->willReturnCallback(function (\Throwable $exception) use (&$loggedException): void {
                $loggedException = $exception;
            });
        $container->add(LoggerInterface::class, $logger, true);

        $provider = new ErrorHandlerServiceProvider();
        $provider->setContainer($container);
        $provider->boot();

        $GLOBALS['__test_whoops'] = $whoops;

        $handlers = $whoops->getHandlers();
        $closureHandler = end($handlers);

        $exception = new RuntimeException('boom');
        $closureHandler->setException($exception);
        $closureHandler->handle();

        self::assertSame($exception, $loggedException);
    }

    private function makeConfig(): ConfigInterface
    {
        return new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed { return $default; }
            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string { return $default; }
            public function getInt(string $key, int $default = 0): int { return $default; }
            public function getBool(string $key, bool $default = false): bool { return $default; }
        };
    }

    private function makeDebugConfig(bool $debug): ConfigInterface
    {
        return new class ($debug) implements ConfigInterface {
            public function __construct(private readonly bool $debug) {}
            public function get(string $key, mixed $default = null): mixed { return $default; }
            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string { return $default; }
            public function getInt(string $key, int $default = 0): int { return $default; }
            public function getBool(string $key, bool $default = false): bool
            {
                return $key === 'app.debug' ? $this->debug : $default;
            }
        };
    }

    private function makeWebProvider(): ErrorHandlerServiceProvider
    {
        return new class extends ErrorHandlerServiceProvider {
            protected function isCli(): bool
            {
                return false;
            }
        };
    }

    /** @param array<int, object> $handlers */
    private function containsHandler(array $handlers, string $class): bool
    {
        foreach ($handlers as $handler) {
            if ($handler instanceof $class) {
                return true;
            }
        }

        return false;
    }
}
