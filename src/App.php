<?php declare(strict_types=1);

namespace Concept\Core;

use Concept\Core\Components\Logger\DebugLogger;
use Concept\Core\Events\Telemetry\ApplicationTelemetryBuffer;
use Concept\Core\Http\Protocol\HttpStatusCode;
use Concept\Core\Components\Path\PathManager;
use InvalidArgumentException;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Concept\Core\Events\Http\RouterDispatchFinished;
use Concept\Core\Events\Http\RouterDispatchStarted;
use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Container\ServiceProvider\ServiceProviderInterface;
use League\Event\EventDispatcher;
use League\Route\Router;
use PHPUnit\Event\Code\Throwable;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Whoops\Handler\Handler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as Whoops;

final class App
{
    private const string FALLBACK_FILE_PATH = '%s/resources/views/errors/fallback/500.php';
    private const string ERR_PROVIDERS_NOT_FOUND = 'Providers file not found at: %s';
    private const string ERR_PROVIDERS_NOT_ARRAY = 'Providers file must return an array: %s';
    private const string ERR_ROUTES_NOT_FOUND = 'Routes file not found at: %s';

    protected Container $container;
    protected string $rootPath;

    /** @var array<ServiceProviderInterface> */
    protected array $providers = [];

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
    public static function create(string $rootPath, array $paths): static
    {
        return new static($rootPath, $paths);
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

    /**
     * @param array<string> $routePaths
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    public function registerRoutes(array $routePaths): void
    {
        $container = $this->container;
        /** @var Router $router */
        $router = $container->get(Router::class);

        foreach ($routePaths as $routesFileName) {
            if (!file_exists($routesFileName)) {
                throw new InvalidArgumentException(sprintf(self::ERR_ROUTES_NOT_FOUND, $routesFileName));
            }

            require $routesFileName;
        }
    }

    public function run(): void
    {
        /** @var Router $router */
        $router = $this->container->get(Router::class);
        /** @var ServerRequestInterface $request */
        $request = $this->container->get(ServerRequestInterface::class);

        $eventDispatcher = $this->peekEventDispatcher();
        $eventDispatcher?->dispatch(new RouterDispatchStarted($request));
        $startedAt = microtime(true);

        $response = null;
        try {
            $response = $router->dispatch($request);
        } finally {
            $eventDispatcher?->dispatch(
                new RouterDispatchFinished(
                    $request,
                    $response,
                    $startedAt ? microtime(true) - $startedAt : 0,
                ),
            );

            /** @var ApplicationTelemetryBuffer $telemetry */
            $telemetry = $this->container->get(ApplicationTelemetryBuffer::class);
            DebugLogger::log($telemetry->statistics());
        }

        (new SapiEmitter)->emit($response);
    }

    /**
     * Registers Whoops before the container and service providers finish booting so failures
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
        } else {
            if (PHP_SAPI === 'cli') {
                $whoops->pushHandler(new PlainTextHandler());
            } else {
                $whoops->pushHandler(function(Throwable $exception) {
                    http_response_code(HttpStatusCode::INTERNAL_SERVER_ERROR);
                    $fallbackFileName = sprintf(self::FALLBACK_FILE_PATH, $this->rootPath);
                    if (file_exists($fallbackFileName)) {
                        include $fallbackFileName;
                    }

                    return Handler::QUIT;
                });
            }
        }

        $whoops->register();

        $this->container->add(Whoops::class, $whoops)->setShared(true);
    }

    private function peekEventDispatcher(): ?EventDispatcher
    {
        if (!$this->container->has(EventDispatcherInterface::class)) {
            return null;
        }

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $this->container->get(EventDispatcherInterface::class);

        return $dispatcher;
    }
}
