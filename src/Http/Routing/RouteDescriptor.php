<?php declare(strict_types=1);

namespace Concept\Core\Http\Routing;

use Closure;
use Concept\Core\Http\Routing\Contracts\RouterInterface;
use Laravel\SerializableClosure\SerializableClosure;
use League\Route\Route;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use ReflectionProperty;

final class RouteDescriptor
{
    public function __construct(private readonly RouterInterface $router) {}

    /**
     * @return list<Route>
     */
    public function all(): array
    {
        $reflection = new ReflectionClass($this->router);

        $collectGroupRoutes = $reflection->getMethod('collectGroupRoutes');
        $collectGroupRoutes->setAccessible(true);
        $collectGroupRoutes->invoke($this->router);

        $buildNameIndex = $reflection->getMethod('buildNameIndex');
        $buildNameIndex->setAccessible(true);
        $buildNameIndex->invoke($this->router);

        /** @var array<int|string, Route> $directRoutes */
        $directRoutes = $this->readRouterProperty($reflection, 'routes');
        /** @var array<string, Route> $namedRoutes */
        $namedRoutes = $this->readRouterProperty($reflection, 'namedRoutes');

        return array_values(array_merge($directRoutes, $namedRoutes));
    }

    /**
     * @return array{
     *     method: string,
     *     path: string,
     *     name: string|null,
     *     action: string,
     *     middleware: list<string>,
     *     group_prefix: string|null,
     *     vars: array<string, mixed>
     * }
     */
    public function describe(Route $route, bool $fullMiddlewareClassNames = false): array
    {
        return [
            'method' => $this->formatMethods($route),
            'path' => $route->getPath(),
            'name' => $route->getName(),
            'action' => $this->action($route),
            'middleware' => $this->middleware($route, $fullMiddlewareClassNames),
            'group_prefix' => $route->getParentGroup()?->getPrefix(),
            'vars' => $route->getVars(),
        ];
    }

    public function formatMethods(Route $route): string
    {
        $methods = $route->getMethod();

        if (is_string($methods)) {
            return $methods;
        }

        return implode('|', $methods);
    }

    public function action(Route $route): string
    {
        $property = new ReflectionProperty(Route::class, 'handler');
        $property->setAccessible(true);
        $handler = $property->getValue($route);

        if ($handler instanceof SerializableClosure || $handler instanceof Closure) {
            return 'closure';
        }

        if (is_string($handler)) {
            return $handler;
        }

        if (is_array($handler)) {
            [$target, $method] = $handler;

            if (is_object($target)) {
                return $target::class . '::' . $this->stringify($method);
            }

            return $this->stringify($target) . '::' . $this->stringify($method);
        }

        if ($handler instanceof RequestHandlerInterface) {
            return $handler::class . '::handle';
        }

        return 'unknown';
    }

    /**
     * @return list<string>
     */
    public function middleware(Route $route, bool $fullClassName = false): array
    {
        $middleware = [];

        foreach ($this->router->getMiddlewareStack() as $item) {
            $middleware[] = $this->formatMiddleware($item, $fullClassName);
        }

        $group = $route->getParentGroup();
        if ($group !== null) {
            foreach ($group->getMiddlewareStack() as $item) {
                $middleware[] = $this->formatMiddleware($item, $fullClassName);
            }
        }

        foreach ($route->getMiddlewareStack() as $item) {
            $middleware[] = $this->formatMiddleware($item, $fullClassName);
        }

        return $middleware;
    }

    /**
     * @param ReflectionClass<RouterInterface> $reflection
     */
    private function readRouterProperty(ReflectionClass $reflection, string $propertyName): mixed
    {
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($this->router);
    }

    private function formatMiddleware(mixed $middleware, bool $fullClassName): string
    {
        $className = match (true) {
            is_string($middleware) => $middleware,
            $middleware instanceof MiddlewareInterface => $middleware::class,
            default => null,
        };

        if ($className === null) {
            return 'unknown';
        }

        return $fullClassName ? $className : $this->shortClassName($className);
    }

    private function shortClassName(string $className): string
    {
        $position = strrpos($className, '\\');

        if ($position === false) {
            return $className;
        }

        return substr($className, $position + 1);
    }

    private function stringify(mixed $value): string
    {
        if (is_string($value)) {
            return $value;
        }

        if (is_scalar($value)) {
            return (string) $value;
        }

        return '';
    }
}
