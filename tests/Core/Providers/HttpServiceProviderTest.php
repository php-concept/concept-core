<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Routing\Contracts\UrlGeneratorInterface;
use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Components\View\Contracts\ViewResponseFactoryInterface;
use Concept\Core\Components\View\ViewResponseFactory;
use Concept\Core\Http\RequestFormat;
use Concept\Core\Http\ResponseFactory;
use Concept\Core\Providers\HttpServiceProvider;
use Illuminate\Pagination\Paginator;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use InvalidArgumentException;
use League\Container\Container;
use League\Route\Router;
use PHPUnit\Framework\TestCase;
use Concept\Core\Http\Contracts\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HttpServiceProviderTest extends TestCase
{
    public function testProvidesExpectedHttpServices(): void
    {
        $provider = new HttpServiceProvider();

        self::assertTrue($provider->provides(ServerRequestInterface::class));
        self::assertTrue($provider->provides(Router::class));
        self::assertTrue($provider->provides(UrlGeneratorInterface::class));
        self::assertTrue($provider->provides(RequestFormat::class));
        self::assertTrue($provider->provides(ResponseFactoryInterface::class));
        self::assertFalse($provider->provides(ResponseFactory::class));
        self::assertTrue($provider->provides(ViewResponseFactoryInterface::class));
    }

    public function testRegisterAndBootBindServicesAndConfigurePaginatorResolvers(): void
    {
        $container = new Container();

        // prebind request to make paginator assertions deterministic
        $request = (new ServerRequest())
            ->withUri(new Uri('https://app.test/list'))
            ->withQueryParams(['page' => '4']);
        $container->add(ServerRequestInterface::class, $request, true);
        $container->add(ConfigInterface::class, $this->createConfig([]), true);

        $provider = new HttpServiceProvider();
        $provider->setContainer($container);
        $provider->register();
        $provider->boot();

        self::assertInstanceOf(RequestFormat::class, $container->get(RequestFormat::class));
        self::assertInstanceOf(Router::class, $container->get(Router::class));
        self::assertInstanceOf(UrlGeneratorInterface::class, $container->get(UrlGeneratorInterface::class));
        self::assertInstanceOf(ResponseFactoryInterface::class, $container->get(ResponseFactoryInterface::class));
        self::assertFalse($container->has(ResponseFactory::class));

        $container->add(ViewInterface::class, $this->createStub(ViewInterface::class));
        self::assertInstanceOf(ViewResponseFactoryInterface::class, $container->get(ViewResponseFactoryInterface::class));
        self::assertFalse($container->has(ViewResponseFactory::class));

        self::assertSame(4, Paginator::resolveCurrentPage());
        self::assertSame('/list', Paginator::resolveCurrentPath());
    }

    public function testRegisterBuildsServerRequestFromGlobalsWhenNotPrebound(): void
    {
        $container = new Container();
        $container->add(ConfigInterface::class, $this->createConfig([]), true);

        $provider = new HttpServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        self::assertInstanceOf(ServerRequestInterface::class, $container->get(ServerRequestInterface::class));
    }

    public function testRegisterLoadsConfiguredRouteFiles(): void
    {
        $tmpRoot = sys_get_temp_dir() . '/http-provider-routes-' . bin2hex(random_bytes(6));
        mkdir($tmpRoot, 0777, true);

        $marker = $tmpRoot . '/routes-loaded.flag';
        $routesFile = $tmpRoot . '/routes.php';
        file_put_contents(
            $routesFile,
            "<?php file_put_contents('" . $marker . "', 'loaded');"
        );

        try {
            $container = new Container();
            $container->add(ConfigInterface::class, $this->createConfig([$routesFile]), true);

            $provider = new HttpServiceProvider();
            $provider->setContainer($container);
            $provider->register();
            $container->get(Router::class);

            self::assertFileExists($marker);
            self::assertSame('loaded', file_get_contents($marker));
        } finally {
            if (is_file($marker)) {
                unlink($marker);
            }
            if (is_file($routesFile)) {
                unlink($routesFile);
            }
            if (is_dir($tmpRoot)) {
                rmdir($tmpRoot);
            }
        }
    }

    public function testRegisterThrowsWhenConfiguredRouteFileIsMissing(): void
    {
        $container = new Container();
        $container->add(
            ConfigInterface::class,
            $this->createConfig([sys_get_temp_dir() . '/missing-routes-' . bin2hex(random_bytes(6)) . '.php']),
            true
        );

        $provider = new HttpServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Routes file not found at:');

        $container->get(Router::class);
    }

    /**
     * @param array<string> $routes
     */
    private function createConfig(array $routes): ConfigInterface
    {
        return new class($routes) implements ConfigInterface {
            /**
             * @param array<string> $routes
             */
            public function __construct(private readonly array $routes) {}

            public function get(string $key, mixed $default = null): mixed
            {
                return $key === 'routes' ? $this->routes : $default;
            }

            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string { return $default; }
            public function getInt(string $key, int $default = 0): int { return $default; }
            public function getBool(string $key, bool $default = false): bool { return $default; }
        };
    }
}
