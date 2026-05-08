<?php declare(strict_types=1);

namespace Tests\Core\Http;

use Concept\Core\Components\Caster\Contracts\CasterInterface;
use Concept\Core\Components\Validator\Exceptions\ValidationException;
use Concept\Core\Http\RouteStrategy;
use Concept\Core\Http\Requests\FormRequestInterface;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use League\Container\Container;
use League\Route\Route;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RouteStrategyTest extends TestCase
{
    public function testInvokesClosureAndCastsRouteParameterUsingCaster(): void
    {
        $container = new Container();

        $caster = $this->createMock(CasterInterface::class);
        $caster->expects(self::once())
            ->method('cast')
            ->with('15', 'int')
            ->willReturn(15);
        $container->add(CasterInterface::class, $caster, true);

        $strategy = new RouteStrategy();
        $strategy->setContainer($container);

        $route = new Route('GET', '/users/{id}', function (int $id): ResponseInterface {
            return (new Response())->withHeader('X-Id', (string) $id);
        }, null, ['id' => '15']);

        $request = new ServerRequest();

        $response = $strategy->invokeRouteCallable($route, $request);

        self::assertSame('15', $response->getHeaderLine('X-Id'));
        self::assertTrue($container->has(ServerRequestInterface::class));
        /** @var ServerRequestInterface $stored */
        $stored = $container->get(ServerRequestInterface::class);
        self::assertSame('15', $stored->getAttribute('id'));
    }

    public function testInjectsServerRequestParameterAndDefaultValue(): void
    {
        $container = new Container();
        $strategy = new RouteStrategy();
        $strategy->setContainer($container);

        $route = new Route('GET', '/x/{id}', function (ServerRequestInterface $request, string $missing = 'fallback'): ResponseInterface {
            return (new Response())
                ->withHeader('X-Missing', $missing)
                ->withHeader('X-Route-Id', (string) $request->getAttribute('id', 'none'));
        }, null, ['id' => '42']);

        $response = $strategy->invokeRouteCallable($route, new ServerRequest());

        self::assertSame('fallback', $response->getHeaderLine('X-Missing'));
        self::assertSame('42', $response->getHeaderLine('X-Route-Id'));
    }

    public function testResolvesValidFormRequestFromContainer(): void
    {
        $container = new Container();

        $formRequest = $this->createStub(TestFormRequest::class);
        $formRequest->method('validate')->willReturn(true);

        $container->add(TestFormRequest::class, $formRequest, true);

        $strategy = new RouteStrategy();
        $strategy->setContainer($container);

        $route = new Route('GET', '/form', function (TestFormRequest $request): ResponseInterface {
            return (new Response())->withHeader('X-Form', $request instanceof FormRequestInterface ? 'ok' : 'no');
        });

        $response = $strategy->invokeRouteCallable($route, new ServerRequest());

        self::assertSame('ok', $response->getHeaderLine('X-Form'));
    }

    public function testThrowsValidationExceptionForInvalidFormRequest(): void
    {
        $container = new Container();

        $formRequest = $this->createStub(TestFormRequest::class);
        $formRequest->method('validate')->willReturn(false);
        $formRequest->method('errors')->willReturn(['title' => ['required']]);
        $formRequest->method('all')->willReturn(['title' => '']);

        $container->add(TestFormRequest::class, $formRequest, true);

        $strategy = new RouteStrategy();
        $strategy->setContainer($container);

        $route = new Route('GET', '/form', function (TestFormRequest $request): ResponseInterface {
            return new Response();
        });

        $this->expectException(ValidationException::class);

        $strategy->invokeRouteCallable($route, new ServerRequest());
    }

    public function testUsesInvokableClassHandler(): void
    {
        $container = new Container();
        $strategy = new RouteStrategy();
        $strategy->setContainer($container);

        $route = new Route('GET', '/invokable/{id}', new InvokableRouteHandler(), null, ['id' => '7']);
        $response = $strategy->invokeRouteCallable($route, new ServerRequest());

        self::assertSame('7', $response->getHeaderLine('X-Invokable-Id'));
    }

    public function testUsesArrayCallableClassMethodHandler(): void
    {
        $container = new Container();
        $strategy = new RouteStrategy();
        $strategy->setContainer($container);

        $route = new Route(
            'GET',
            '/posts/{slug}',
            [new ClassMethodRouteHandler(), 'show'],
            null,
            ['slug' => 'hello-world']
        );

        $response = $strategy->invokeRouteCallable($route, new ServerRequest());

        self::assertSame('hello-world', $response->getHeaderLine('X-Route-Slug'));
    }

    public function testPassesNullForUntypedParameterMissingFromRouteVars(): void
    {
        $container = new Container();
        $strategy = new RouteStrategy();
        $strategy->setContainer($container);

        $route = new Route('GET', '/x', function ($missing): ResponseInterface {
            return (new Response())->withHeader('X-Missing-Type', $missing === null ? 'null' : 'set');
        });

        $response = $strategy->invokeRouteCallable($route, new ServerRequest());

        self::assertSame('null', $response->getHeaderLine('X-Missing-Type'));
    }
}

abstract class TestFormRequest implements FormRequestInterface
{
}

final class InvokableRouteHandler
{
    public function __invoke(int $id): ResponseInterface
    {
        return (new Response())->withHeader('X-Invokable-Id', (string) $id);
    }
}

final class ClassMethodRouteHandler
{
    public function show(string $slug): ResponseInterface
    {
        return (new Response())->withHeader('X-Route-Slug', $slug);
    }
}
