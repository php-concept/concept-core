<?php declare(strict_types=1);

namespace Tests\Core\Http\Routing;

use Concept\Core\Services\Caster\Contracts\CasterInterface;
use Concept\Core\Services\Validator\Exceptions\ValidationException;
use Concept\Core\Services\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Http\Contracts\RouteInterceptorInterface;
use Concept\Core\Http\Routing\RouteStrategy;
use RuntimeException;
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

    public function testSkipsInterceptorsWhenConfigIsNotRegistered(): void
    {
        $container = new Container();
        $strategy = new RouteStrategy();
        $strategy->setContainer($container);

        $route = (new Route('GET', '/open', fn (): ResponseInterface => new Response()))->setName('open');

        $response = $strategy->invokeRouteCallable($route, new ServerRequest());

        self::assertSame(200, $response->getStatusCode());
    }

    public function testRunsConfiguredInterceptorsBeforeRouteHandler(): void
    {
        $container = new Container();

        $interceptor = new RecordingRouteInterceptor();
        $container->add(RecordingRouteInterceptor::class, $interceptor, true);
        $container->add(ConfigInterface::class, $this->createInterceptorConfig([RecordingRouteInterceptor::class]), true);

        $strategy = new RouteStrategy();
        $strategy->setContainer($container);

        $route = (new Route('GET', '/users/{id}', fn (): ResponseInterface => new Response(), null, ['id' => '7']))
            ->setName('users.index');

        $strategy->invokeRouteCallable($route, new ServerRequest());

        self::assertSame('users.index', $interceptor->route?->getName());
        self::assertSame(['id' => '7'], $interceptor->route?->getVars());
    }

    public function testRunsInterceptorsInConfiguredOrder(): void
    {
        $container = new Container();
        $order = new InterceptorOrder();

        $container->add(FirstRouteInterceptor::class, new FirstRouteInterceptor($order), true);
        $container->add(SecondRouteInterceptor::class, new SecondRouteInterceptor($order), true);
        $container->add(
            ConfigInterface::class,
            $this->createInterceptorConfig([FirstRouteInterceptor::class, SecondRouteInterceptor::class]),
            true,
        );

        $strategy = new RouteStrategy();
        $strategy->setContainer($container);

        $route = new Route('GET', '/ordered', fn (): ResponseInterface => new Response());
        $strategy->invokeRouteCallable($route, new ServerRequest());

        self::assertSame([FirstRouteInterceptor::class, SecondRouteInterceptor::class], $order->classes);
    }

    public function testPropagatesExceptionFromInterceptor(): void
    {
        $container = new Container();

        $container->add(DenyingRouteInterceptor::class, new DenyingRouteInterceptor(), true);
        $container->add(ConfigInterface::class, $this->createInterceptorConfig([DenyingRouteInterceptor::class]), true);

        $strategy = new RouteStrategy();
        $strategy->setContainer($container);

        $route = (new Route('GET', '/admin', fn (): ResponseInterface => new Response()))->setName('admin.dashboard');

        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('Interceptor denied');

        $strategy->invokeRouteCallable($route, new ServerRequest());
    }

    /**
     * @param list<class-string<RouteInterceptorInterface>> $interceptors
     */
    private function createInterceptorConfig(array $interceptors): ConfigInterface
    {
        return new class($interceptors) implements ConfigInterface {
            /** @param list<class-string<RouteInterceptorInterface>> $interceptors */
            public function __construct(private readonly array $interceptors) {}

            public function get(string $key, mixed $default = null): mixed
            {
                return $key === ConfigKey::ROUTES_INTERCEPTORS ? $this->interceptors : $default;
            }

            public function set(string $key, mixed $value): void {}

            public function has(string $key): bool
            {
                return false;
            }

            public function all(): array
            {
                return [];
            }

            public function getString(string $key, string $default = ''): string
            {
                return $default;
            }

            public function getInt(string $key, int $default = 0): int
            {
                return $default;
            }

            public function getBool(string $key, bool $default = false): bool
            {
                return $default;
            }
        };
    }
}

final class RecordingRouteInterceptor implements RouteInterceptorInterface
{
    public ?Route $route = null;

    public function intercept(Route $route, ServerRequestInterface $request): void
    {
        $this->route = $route;
    }
}

final class InterceptorOrder
{
    /** @var list<class-string<RouteInterceptorInterface>> */
    public array $classes = [];
}

final class FirstRouteInterceptor implements RouteInterceptorInterface
{
    public function __construct(private readonly InterceptorOrder $order) {}

    public function intercept(Route $route, ServerRequestInterface $request): void
    {
        $this->order->classes[] = self::class;
    }
}

final class SecondRouteInterceptor implements RouteInterceptorInterface
{
    public function __construct(private readonly InterceptorOrder $order) {}

    public function intercept(Route $route, ServerRequestInterface $request): void
    {
        $this->order->classes[] = self::class;
    }
}

final class DenyingRouteInterceptor implements RouteInterceptorInterface
{
    public function intercept(Route $route, ServerRequestInterface $request): void
    {
        throw new RuntimeException('Interceptor denied');
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
