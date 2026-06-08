<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Http\Routing\Contracts\UrlGeneratorInterface;
use Concept\Core\Http\Routing\UrlGenerator;
use Concept\Core\Components\Telemetry\TelemetryEvent;
use Concept\Core\Components\Telemetry\TelemetryTrait;
use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Components\View\Contracts\ViewResponseFactoryInterface;
use Concept\Core\Components\View\ViewResponseFactory;
use Concept\Core\Http\Contracts\ResponseFactoryInterface;
use Concept\Core\Http\RequestFormat;
use Concept\Core\Http\ResponseFactory;
use Concept\Core\Http\RouteStrategy;
use Illuminate\Pagination\Paginator;
use InvalidArgumentException;
use Laminas\Diactoros\ServerRequestFactory;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use League\Route\Router;
use Psr\Http\Message\ServerRequestInterface;

class HttpServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    use TelemetryTrait;

    private const string ERR_ROUTES_NOT_FOUND = 'Routes file not found at: %s';

    public function provides(string $id): bool
    {
        $services = [
            ServerRequestInterface::class,
            Router::class,
            UrlGeneratorInterface::class,
            RequestFormat::class,
            ResponseFactoryInterface::class,
            ViewResponseFactoryInterface::class,
        ];

        return in_array($id, $services);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(ServerRequestInterface::class, function () {
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, ServerRequestInterface::class);

            return ServerRequestFactory::fromGlobals(
                $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
            );
        })->setShared(true);

        $container->add(Router::class, function () use ($container) {
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, Router::class);

            $router = new Router();

            $strategy = new RouteStrategy();
            $strategy->setContainer($container);
            $router->setStrategy($strategy);

            if ($container->has(ConfigInterface::class)) {
                /** @var ConfigInterface $config */
                $config = $container->get(ConfigInterface::class);
                /** @var array<string> $routes */
                $routes = $config->get(ConfigKey::ROUTES, []);
                $this->registerRoutes($router, $routes);
            }

            return $router;
        })->setShared(true);

        $container->add(UrlGeneratorInterface::class, function () use ($container) {
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, UrlGeneratorInterface::class);

            /** @var ServerRequestInterface $request */
            $request = $container->get(ServerRequestInterface::class);
            /** @var Router $router */
            $router = $container->get(Router::class);

            return new UrlGenerator($request, $router);
        })->setShared(true);

        $container->add(RequestFormat::class, function () {
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, RequestFormat::class);

            return new RequestFormat();
        })->setShared(true);

        $container->add(ResponseFactoryInterface::class, function () use ($container) {
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, ResponseFactoryInterface::class);

            return new ResponseFactory($container);
        })->setShared(true);

        $container->add(ViewResponseFactoryInterface::class, function () use ($container) {
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, ViewResponseFactoryInterface::class);

            /** @var ServerRequestInterface $request */
            $request = $container->get(ServerRequestInterface::class);
            /** @var ResponseFactoryInterface $responseFactory */
            $responseFactory = $container->get(ResponseFactoryInterface::class);
            /** @var ViewInterface $view */
            $view = $container->get(ViewInterface::class);

            return new ViewResponseFactory($request, $responseFactory, $view);
        })->setShared(true);
    }

    public function boot(): void
    {
        $this->configurePaginator();
    }

    /**
     * @param Router $router
     * @param array<string> $routePaths
     * @return void
     */
    private function registerRoutes(Router $router, array $routePaths): void
    {
        $container = $this->getContainer();
        foreach ($routePaths as $routesFileName) {
            if (!file_exists($routesFileName)) {
                throw new InvalidArgumentException(sprintf(self::ERR_ROUTES_NOT_FOUND, $routesFileName));
            }

            require $routesFileName;
        }
    }

    private function configurePaginator(): void
    {
        $container = $this->getContainer();

        Paginator::currentPageResolver(function ($pageName = 'page') use ($container) {
            /** @var ServerRequestInterface $request */
            $request = $container->get(ServerRequestInterface::class);
            $params = $request->getQueryParams();

            return (int)($params[$pageName] ?? 1);
        });

        Paginator::currentPathResolver(function () use ($container) {
            /** @var ServerRequestInterface $request */
            $request = $container->get(ServerRequestInterface::class);

            return $request->getUri()->getPath();
        });
    }
}
