<?php declare(strict_types=1);

namespace Tests\Core;

use Concept\Core\App;
use Concept\Core\Components\Path\PathManager;
use InvalidArgumentException;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use League\Route\Router;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;
use Tests\Fixtures\Core\DummyServiceProvider;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as Whoops;

final class AppTest extends TestCase
{
    private string $tempRoot;

    private ?App $app = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempRoot = sys_get_temp_dir() . '/concept-app-tests-' . bin2hex(random_bytes(6));
        mkdir($this->tempRoot, 0777, true);
    }

    protected function tearDown(): void
    {
        if ($this->app !== null) {
            try {
                /** @var Whoops $whoops */
                $whoops = $this->app->getContainer()->get(Whoops::class);
                $whoops->unregister();
            } catch (Throwable) {
            }
            $this->app = null;
        }

        foreach (glob($this->tempRoot . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }

        if (is_dir($this->tempRoot)) {
            rmdir($this->tempRoot);
        }

        parent::tearDown();
    }

    public function testCreateRegistersPathManagerInContainer(): void
    {
        $this->app = App::create($this->tempRoot, [
            PathManager::LOGS_DIR => 'storage/logs',
        ]);

        $pathManager = $this->app->getContainer()->get(PathManager::class);

        self::assertInstanceOf(PathManager::class, $pathManager);
        self::assertSame($this->tempRoot . '/storage/logs', $pathManager->get(PathManager::LOGS_DIR));
    }

    public function testRegisterServiceProvidersThrowsForMissingFile(): void
    {
        $this->app = App::create($this->tempRoot, []);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Providers file not found at:');

        $this->app->registerServiceProviders([$this->tempRoot . '/missing-providers.php']);
    }

    public function testRegisterServiceProvidersThrowsWhenFileDoesNotReturnArray(): void
    {
        $providersFile = $this->tempRoot . '/providers-invalid.php';
        file_put_contents($providersFile, "<?php return 'not-an-array';");

        $this->app = App::create($this->tempRoot, []);

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Providers file must return an array:');

        $this->app->registerServiceProviders([$providersFile]);
    }

    public function testRegisterServiceProvidersRegistersProviderServices(): void
    {
        $providersFile = $this->tempRoot . '/providers-valid.php';
        file_put_contents(
            $providersFile,
            "<?php return [\\Tests\\Fixtures\\Core\\DummyServiceProvider::class];"
        );

        $this->app = App::create($this->tempRoot, []);
        $this->app->registerServiceProviders([$providersFile]);

        self::assertTrue($this->app->getContainer()->has('dummy.service'));
        self::assertSame('ok', $this->app->getContainer()->get('dummy.service'));
    }

    /**
     * Verifies that registering a non-existent service provider class 
     * results in an Error (class not found).
     */
    public function testRegisterServiceProvidersThrowsWhenClassDoesNotExist(): void
    {
        $providersFile = $this->tempRoot . '/providers-nonexistent.php';
        file_put_contents(
            $providersFile,
            "<?php return ['NonExistent\\ProviderClass'];"
        );

        $this->app = App::create($this->tempRoot, []);
        
        $this->expectException(\Error::class);
        $this->app->registerServiceProviders([$providersFile]);
    }

    /**
     * Verifies that registering a class that does not implement ServiceProviderInterface
     * is handled by the container or the registration logic.
     */
    public function testRegisterServiceProvidersThrowsWhenClassDoesNotImplementInterface(): void
    {
        $providersFile = $this->tempRoot . '/providers-wrong-interface.php';
        file_put_contents(
            $providersFile,
            "<?php return [\\stdClass::class];"
        );

        $this->app = App::create($this->tempRoot, []);
        
        $this->expectException(\TypeError::class);
        $this->app->registerServiceProviders([$providersFile]);
    }

    public function testGetRootPathReturnsConfiguredRoot(): void
    {
        $this->app = App::create($this->tempRoot, []);

        self::assertSame($this->tempRoot, $this->app->getRootPath());
    }

    public function testRegisterRoutesThrowsForMissingFile(): void
    {
        $this->app = App::create($this->tempRoot, []);
        $this->app->getContainer()->add(Router::class, $this->createStub(Router::class), true);

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Routes file not found at:');

        $this->app->registerRoutes([$this->tempRoot . '/missing-routes.php']);
    }

    public function testRegisterRoutesIncludesProvidedRouteFiles(): void
    {
        $marker = $this->tempRoot . '/routes-loaded.flag';
        $routesFile = $this->tempRoot . '/routes.php';
        file_put_contents(
            $routesFile,
            "<?php file_put_contents('" . $marker . "', 'loaded');"
        );

        $this->app = App::create($this->tempRoot, []);
        $this->app->getContainer()->add(Router::class, $this->createStub(Router::class), true);
        $this->app->registerRoutes([$routesFile]);

        self::assertFileExists($marker);
        self::assertSame('loaded', file_get_contents($marker));
    }

    /**
     * Verifies that route files can be registered multiple times across different App instances.
     * This ensures that 'require' is used instead of 'require_once'.
     */
    public function testRegisterRoutesCanBeCalledMultipleTimes(): void
    {
        $routesFile = $this->tempRoot . '/routes-multi.php';
        file_put_contents($routesFile, "<?php \$router->get('/test', fn() => 'ok');");

        // First instance
        $app1 = App::create($this->tempRoot, []);
        $router1 = $this->createMock(Router::class);
        $router1->expects(self::once())->method('get');
        $app1->getContainer()->add(Router::class, $router1, true);
        $app1->registerRoutes([$routesFile]);

        // Second instance in the same process
        $app2 = App::create($this->tempRoot, []);
        $router2 = $this->createMock(Router::class);
        $router2->expects(self::once())->method('get');
        $app2->getContainer()->add(Router::class, $router2, true);

        $app2->registerRoutes([$routesFile]);

        // Clean up both to avoid "risky test" (global error handlers)
        /** @var \Whoops\Run $whoops1 */
        $whoops1 = $app1->getContainer()->get(\Whoops\Run::class);
        $whoops1->unregister();

        /** @var \Whoops\Run $whoops2 */
        $whoops2 = $app2->getContainer()->get(\Whoops\Run::class);
        $whoops2->unregister();
    }

    public function testRegisterEarlyErrorHandlerUsesPrettyPageHandlerWhenDebugEnabled(): void
    {
        $previous = $_ENV['APP_DEBUG'] ?? null;
        $_ENV['APP_DEBUG'] = 'true';

        try {
            $this->app = App::create($this->tempRoot, []);

            /** @var Whoops $whoops */
            $whoops = $this->app->getContainer()->get(Whoops::class);
            $handlers = $whoops->getHandlers();

            self::assertNotEmpty($handlers);
            self::assertInstanceOf(PrettyPageHandler::class, $handlers[0]);
        } finally {
            if ($previous === null) {
                unset($_ENV['APP_DEBUG']);
            } else {
                $_ENV['APP_DEBUG'] = $previous;
            }
        }
    }

    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testRunDispatchesRequestThroughRouter(): void
    {
        $app = App::create($this->tempRoot, []);
        $container = $app->getContainer();

        $request = new ServerRequest();
        $response = (new Response())->withHeader('X-Dispatched', 'yes');

        $router = $this->createMock(Router::class);
        $router->expects(self::once())
            ->method('dispatch')
            ->with(self::isInstanceOf(ServerRequestInterface::class))
            ->willReturn($response);

        $container->add(Router::class, $router, true);
        $container->add(ServerRequestInterface::class, $request, true);

        ob_start();
        try {
            $app->run();
        } finally {
            ob_end_clean();
        }

        self::assertInstanceOf(ResponseInterface::class, $response);
    }
}
