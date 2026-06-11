<?php declare(strict_types=1);

namespace Tests\Core\Http\Routing;

use Concept\Core\Http\Routing\RouteDescriptor;
use Concept\Core\Http\Routing\Router;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class RouteDescriptorTest extends TestCase
{
    public function testDescribeReturnsRouteDetailsForDebugBar(): void
    {
        $router = new Router();
        $router->lazyMiddleware(GlobalRouteDescriptorMiddleware::class);
        $router->group('/api', function ($router): void {
            $router->get('/users/{id}', 'UserController::show')
                ->setName('users.show')
                ->lazyMiddleware(RouteRouteDescriptorMiddleware::class);
        });

        $descriptor = new RouteDescriptor($router);
        $routes = $descriptor->all();

        self::assertCount(1, $routes);

        $description = $descriptor->describe($routes[0]);

        self::assertSame('GET', $description['method']);
        self::assertSame('/api/users/{id}', $description['path']);
        self::assertSame('users.show', $description['name']);
        self::assertSame('UserController::show', $description['action']);
        self::assertSame('/api', $description['group_prefix']);
        self::assertSame([], $description['vars']);
        self::assertSame(
            [
                'GlobalRouteDescriptorMiddleware',
                'RouteRouteDescriptorMiddleware',
            ],
            $description['middleware']
        );
    }
}

final class GlobalRouteDescriptorMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }
}

final class RouteRouteDescriptorMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        return $handler->handle($request);
    }
}
