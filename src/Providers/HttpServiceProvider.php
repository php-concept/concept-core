<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Routing\Contracts\UrlGeneratorInterface;
use Concept\Core\Components\Routing\UrlGenerator;
use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Components\View\ViewResponseFactory;
use Concept\Core\Events\Framework\ServiceAwakening;
use Concept\Core\Http\RequestFormat;
use Concept\Core\Http\ResponseFactory;
use Concept\Core\Http\RouteStrategy;
use Concept\Core\Providers\Concerns\PeeksEventDispatcher;
use Illuminate\Pagination\Paginator;
use Laminas\Diactoros\ServerRequestFactory;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use League\Route\Router;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

class HttpServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    use PeeksEventDispatcher;

    public function provides(string $id): bool
    {
        $services = [
            ServerRequestInterface::class,
            Router::class,
            UrlGeneratorInterface::class,
            RequestFormat::class,
            ResponseFactoryInterface::class,
            ViewResponseFactory::class,
        ];

        return in_array($id, $services);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(ServerRequestInterface::class, function () {
            return ServerRequestFactory::fromGlobals(
                $_SERVER, $_GET, $_POST, $_COOKIE, $_FILES
            );
        })->setShared(true);

        $container->add(Router::class, function () use ($container) {
            $this->peekEventDispatcher()?->dispatch(new ServiceAwakening(Router::class));

            $router = new Router();

            $strategy = new RouteStrategy();
            $strategy->setContainer($container);
            $router->setStrategy($strategy);

            return $router;
        })->setShared(true);

        $container->add(UrlGeneratorInterface::class, function () use ($container) {
            $this->peekEventDispatcher()?->dispatch(new ServiceAwakening(UrlGeneratorInterface::class));

            /** @var Router $router */
            $router = $container->get(Router::class);

            return new UrlGenerator($router);
        })->setShared(true);

        $container->add(RequestFormat::class, function () {
            $this->peekEventDispatcher()?->dispatch(new ServiceAwakening(RequestFormat::class));

            return new RequestFormat();
        })->setShared(true);

        $container->add(ResponseFactoryInterface::class, function () use ($container) {
            $this->peekEventDispatcher()?->dispatch(new ServiceAwakening(ResponseFactory::class));

            return new ResponseFactory($container);
        })->setShared(true);

        $container->add(ViewResponseFactory::class, function () use ($container) {
            $this->peekEventDispatcher()?->dispatch(new ServiceAwakening(ViewResponseFactory::class));

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
