<?php declare(strict_types=1);

namespace Concept\Core;

use Concept\Core\Components\Path\PathManager;
use Concept\Core\Integrations\Whoops\PhpErrorLogHandler;
use Concept\Core\Integrations\Whoops\EarlyBootstrapFallbackHandler;
use InvalidArgumentException;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Container\ServiceProvider\ServiceProviderInterface;
use League\Route\Router;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as Whoops;

final class App
{
    private const string ERR_PROVIDERS_NOT_FOUND = 'Providers file not found at: %s';
    private const string ERR_PROVIDERS_NOT_ARRAY = 'Providers file must return an array: %s';

    protected Container $container;
    protected string $rootPath;

    /**
     * @param string $rootPath
     * @param array<string, string> $paths
     */
    private function __construct(string $rootPath, array $paths)
    {
        $this->rootPath = $rootPath;
        $this->container = new Container();
        $this->registerEarlyErrorHandler();

        $this->container->delegate(new ReflectionContainer(cacheResolutions: true));
        $this->container->add(ContainerInterface::class, fn() => $this->container)
            ->setShared(true);
        $this->container->add(App::class, fn() => $this);
        $this->container->add(PathManager::class, new PathManager($rootPath, $paths));
    }

    /**
     * @param string $rootPath
     * @param array<string, string> $paths
     * @return static
     */
    public static function create(string $rootPath, array $paths): App
    {
        return new App($rootPath, $paths);
    }

    public function getContainer(): ContainerInterface
    {
        return $this->container;
    }

    public function getRootPath(): string
    {
        return $this->rootPath;
    }

    /**
     * @param array<string> $providerPaths
     */
    public function registerServiceProviders(array $providerPaths): void
    {
        foreach ($providerPaths as $providersFileName) {
            if (!file_exists($providersFileName)) {
                throw new InvalidArgumentException(sprintf(self::ERR_PROVIDERS_NOT_FOUND, $providersFileName));
            }

            $providers = require $providersFileName;

            if (!is_array($providers)) {
                throw new RuntimeException(sprintf(self::ERR_PROVIDERS_NOT_ARRAY, $providersFileName));
            }

            foreach ($providers as $providerClassName) {
                /** @var ServiceProviderInterface $provider */
                $provider = new $providerClassName();
                $this->container->addServiceProvider($provider);
            }
        }
    }

    public function run(): void
    {
        if (!$this->container->has(Router::class) || !$this->container->has(ServerRequestInterface::class)) {
            throw new RuntimeException('Router and ServerRequestInterface must be registered before running the application.');
        }

        /** @var Router $router */
        $router = $this->container->get(Router::class);
        /** @var ServerRequestInterface $request */
        $request = $this->container->get(ServerRequestInterface::class);

        $response = $router->dispatch($request);
        (new SapiEmitter)->emit($response);
    }

    /**
     * Registers Whoops before the container and service providers finish booting, so failures
     * during early bootstrap still get a usable error page or CLI output.
     *
     * This installs global PHP exception/error handlers. Automated tests that construct {@see App}
     * must tear that down (e.g. resolve {@see Whoops} from the container and call
     * {@see Whoops::unregister()}) in tearDown to avoid leaking handlers into the next test.
     */
    private function registerEarlyErrorHandler(): void
    {
        $debug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';

        $whoops = new Whoops();

        if ($debug) {
            $whoops->pushHandler(new PrettyPageHandler());
        } elseif (PHP_SAPI === 'cli') {
            $whoops->pushHandler(new PlainTextHandler());
        } else {
            $whoops->pushHandler(new EarlyBootstrapFallbackHandler($this->rootPath));
        }

        // Whoops runs handlers in reverse push order — log first, then render.
        $whoops->pushHandler(new PhpErrorLogHandler());

        $whoops->register();

        $this->container->add(Whoops::class, $whoops)->setShared(true);
    }
}
