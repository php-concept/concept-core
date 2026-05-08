<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Http\RequestFormat;
use Concept\Core\Http\RouteStrategy;
use Illuminate\Pagination\Paginator;
use Laminas\Diactoros\ServerRequestFactory;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use League\Route\Router;
use Psr\Http\Message\ServerRequestInterface;

class HttpServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public function provides(string $id): bool
    {
        $services = [
            ServerRequestInterface::class,
            Router::class,
            RequestFormat::class,
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
            $router = new Router();

            $strategy = new RouteStrategy();
            $strategy->setContainer($container);
            $router->setStrategy($strategy);

            return $router;
        })->setShared(true);

        $container->add(RequestFormat::class, function () {
            return new RequestFormat();
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
