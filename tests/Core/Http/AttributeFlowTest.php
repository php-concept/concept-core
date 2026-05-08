<?php declare(strict_types=1);

namespace Tests\Core\Http;

use Concept\Core\Http\RouteStrategy;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use League\Container\Container;
use League\Route\Router;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

final class AttributeFlowTest extends TestCase
{
    public function testAttributesFlowFromBootstrapThroughMiddlewareToController(): void
    {
        $container = new Container();
        $strategy = new RouteStrategy();
        $strategy->setContainer($container);

        $router = new Router();
        $router->setStrategy($strategy);

        // 1. Рівень Middleware: додає свій атрибут
        $middleware = new class implements MiddlewareInterface {
            public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
            {
                $request = $request->withAttribute('middleware_attr', 'value_from_middleware');
                return $handler->handle($request);
            }
        };

        // 2. Рівень Контролера: перевіряє всі атрибути
        $controller = function (ServerRequestInterface $request) : ResponseInterface {
            // Перевіряємо атрибут з початкового запиту
            TestCase::assertSame('initial_value', $request->getAttribute('bootstrap_attr'));
            
            // Перевіряємо атрибут з Middleware
            TestCase::assertSame('value_from_middleware', $request->getAttribute('middleware_attr'));
            
            // Перевіряємо атрибут з URL (маршрутизація)
            TestCase::assertSame('123', $request->getAttribute('id'));

            return new Response();
        };

        $router->map('GET', '/test/{id}', $controller)->middleware($middleware);

        // 3. Запуск: створюємо початковий запит з атрибутом "Bootstrap"
        $request = (new ServerRequest([], [], '/test/123', 'GET'))
            ->withAttribute('bootstrap_attr', 'initial_value');

        $response = $router->dispatch($request);

        self::assertSame(200, $response->getStatusCode());
    }

    public function testAttributesAreUpdatedInContainerByStrategy(): void
    {
        $container = new Container();
        $strategy = new RouteStrategy();
        $strategy->setContainer($container);

        $router = new Router();
        $router->setStrategy($strategy);

        // Контролер, який НЕ бере реквест з аргументів, а каже контейнеру дати його
        $controller = function () use ($container) : ResponseInterface {
            /** @var ServerRequestInterface $requestFromContainer */
            $requestFromContainer = $container->get(ServerRequestInterface::class);
            
            TestCase::assertSame('dynamic_id', $requestFromContainer->getAttribute('id'));
            return new Response();
        };

        $router->map('GET', '/user/{id}', $controller);

        $request = new ServerRequest([], [], '/user/dynamic_id', 'GET');
        
        // Додаємо початковий реквест в контейнер (як це робить App)
        $container->add(ServerRequestInterface::class, $request, true);

        $router->dispatch($request);
    }
}
