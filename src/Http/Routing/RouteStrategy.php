<?php declare(strict_types=1);

namespace Concept\Core\Http\Routing;

use Closure;
use Concept\Core\Services\Caster\Contracts\CasterInterface;
use Concept\Core\Services\Caster\Exceptions\CastingException;
use Concept\Core\Services\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Services\Telemetry\TelemetryEvent;
use Concept\Core\Services\Telemetry\TelemetryKey;
use Concept\Core\Services\Telemetry\TelemetryTrait;
use Concept\Core\Services\Validator\Exceptions\ValidationException;
use Concept\Core\Http\Contracts\RouteInterceptorInterface;
use Concept\Core\Http\Requests\FormRequestInterface;
use League\Container\DefinitionContainerInterface;
use League\Route\Route;
use League\Route\Strategy\ApplicationStrategy;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use ReflectionException;
use ReflectionFunction;
use ReflectionFunctionAbstract;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;

class RouteStrategy extends ApplicationStrategy
{
    use TelemetryTrait;

    public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface
    {
        $this->runInterceptors($route, $request);

        $request = $this->prepareRequest($route, $request);

        $callable = $route->getCallable($this->getContainer());
        $telemetryId = $this->startTelemetry(TelemetryEvent::HTTP_ROUTE_CALLABLE_INVOKED, $callable, $route);

        try {
            $reflection = $this->getReflection($callable);
            $arguments = $this->resolveArguments($reflection, $request, $route->getVars());

            if (is_array($callable)) {
                /** @var object $object */
                $object = $callable[0];
                /** @phpstan-ignore method.notFound */
                return $reflection->invokeArgs($object, $arguments);
            }

            if (is_object($callable) && !($callable instanceof Closure)) {
                /** @phpstan-ignore method.notFound */
                return $reflection->invokeArgs($callable, $arguments);
            }

            /** @phpstan-ignore-next-line */
            return $reflection->invokeArgs($arguments);
        } finally {
            $this->telemetry()?->finish(TelemetryEvent::HTTP_ROUTE_CALLABLE_INVOKED, (string)$telemetryId);
        }
    }

    private function runInterceptors(Route $route, ServerRequestInterface $request): void
    {
        /** @var DefinitionContainerInterface $container */
        $container = $this->getContainer();

        if (!$container->has(ConfigInterface::class)) {
            return;
        }

        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);
        /** @var list<class-string<RouteInterceptorInterface>> $interceptorClasses */
        $interceptorClasses = $config->get(ConfigKey::ROUTES_INTERCEPTORS, []);

        foreach ($interceptorClasses as $interceptorClass) {
            $telemetryId = $this->telemetry()?->start(TelemetryEvent::HTTP_ROUTE_INTERCEPTOR_EXECUTED, [TelemetryKey::NAME => $interceptorClass]);
            /** @var RouteInterceptorInterface $interceptor */
            $interceptor = $container->get($interceptorClass);
            $interceptor->intercept($route, $request);
            $this->telemetry()?->finish(TelemetryEvent::HTTP_ROUTE_INTERCEPTOR_EXECUTED, (string)$telemetryId);
        }
    }

    /**
     * Prepares the request with the route variables
     *
     * @param Route $route
     * @param ServerRequestInterface $request
     * @return ServerRequestInterface
     */
    private function prepareRequest(Route $route, ServerRequestInterface $request): ServerRequestInterface
    {
        // Add route variables to the request
        foreach ($route->getVars() as $name => $value) {
            $request = $request->withAttribute($name, $value);
        }

        // Refresh the request in the container
        /** @var DefinitionContainerInterface $container */
        $container = $this->getContainer();
        $container->add(ServerRequestInterface::class, $request, true);

        return $request;
    }

    /**
     * Creates a reflection object depending on the handler type (class method or closure)
     *
     * @param callable $callable
     * @return ReflectionFunctionAbstract
     * @throws ReflectionException
     */
    private function getReflection(callable $callable): ReflectionFunctionAbstract
    {
        if (is_array($callable)) {
            return new ReflectionMethod($callable[0], $callable[1]);
        }

        if ($callable instanceof Closure) {
            return new ReflectionFunction($callable);
        }

        /** @phpstan-ignore-next-line */
        return new ReflectionMethod($callable, '__invoke');
    }

    /**
     * Resolves arguments for the given reflection object (Auto-wiring)
     *
     * @param ReflectionFunctionAbstract $reflection
     * @param ServerRequestInterface $request
     * @param array<string> $vars
     * @return array<mixed>
     * @throws CastingException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws ValidationException
     */
    private function resolveArguments(
        ReflectionFunctionAbstract $reflection,
        ServerRequestInterface $request,
        array $vars): array
    {
        $arguments = [];
        foreach ($reflection->getParameters() as $parameter) {
            $arguments[] = $this->resolveParameter($parameter, $request, $vars);
        }

        return $arguments;
    }

    /**
     * Resolves arguments for the given reflection object
     *
     * @param ReflectionParameter $parameter
     * @param ServerRequestInterface $request
     * @param array<string> $vars
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws CastingException
     * @throws ValidationException
     */
    private function resolveParameter(
        ReflectionParameter $parameter,
        ServerRequestInterface $request,
        array $vars): mixed
    {
        $type = $parameter->getType();
        $typeName = $type instanceof ReflectionNamedType ? $type->getName() : null;

        if ($typeName !== null) {
            // 1. If the parameter is a FormRequest, validate and return it
            if (is_subclass_of($typeName, FormRequestInterface::class)) {
                return $this->resolveFormRequest($typeName);
            }

            // 2. If the parameter is a ServerRequest, return it
            if ($typeName === ServerRequestInterface::class
                    || is_subclass_of($typeName, ServerRequestInterface::class)) {
                return $request;
            }
        }

        // 3. If the parameter is a variable from the URL (with type casting)
        $name = $parameter->getName();
        if (isset($vars[$name])) {
            return $this->castValue($vars[$name], $typeName);
        }

        // 4. Default value if available
        if ($parameter->isDefaultValueAvailable()) {
            return $parameter->getDefaultValue();
        }

        return null;
    }

    /**
     * Creates and validates a FormRequest instance
     *
     * @param string $className
     * @return FormRequestInterface
     * @throws ValidationException
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function resolveFormRequest(string $className): FormRequestInterface
    {
        /** @var DefinitionContainerInterface $container */
        $container = $this->getContainer();
        /** @var FormRequestInterface $formRequest */
        $formRequest = $container->get($className);

        $telemetryId = $this->telemetry()?->start(TelemetryEvent::HTTP_FORM_REQUEST_VALIDATED, [TelemetryKey::NAME => $className]);
        try {
            if (!$formRequest->validate()) {
                throw new ValidationException($formRequest->errors(), $formRequest->all());
            }

            return $formRequest;
        } finally {
            $this->telemetry()?->finish(TelemetryEvent::HTTP_FORM_REQUEST_VALIDATED, (string) $telemetryId);
        }
    }

    /**
     * Casts value using the system Caster retrieved from the container
     *
     * @param mixed $value
     * @param string|null $type
     * @return mixed
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws CastingException
     */
    private function castValue(mixed $value, ?string $type): mixed
    {
        /** @var DefinitionContainerInterface $container */
        $container = $this->getContainer();
        if ($type === null || !$container->has(CasterInterface::class)) {
            return $value;
        }

        /** @var CasterInterface $caster */
        $caster = $container->get(CasterInterface::class);

        return $caster->cast($value, $type);
    }

    private function startTelemetry(string $telemetryEventName, callable $callable, Route $route): ?string
    {
        $handlerLabel = $this->describeCallable($callable);

        return $this->telemetry()?->start(
            $telemetryEventName,
            [
                TelemetryKey::ROUTE => $route,
                TelemetryKey::HANDLER => $handlerLabel,
            ]
        );
    }

    private function describeCallable(callable $callable): string
    {
        if ($callable instanceof Closure) {
            return 'closure';
        }

        if (is_string($callable)) {
            return $callable;
        }

        if (is_array($callable)) {
            $target = $callable[0];
            $method = $callable[1];

            return (is_object($target) ? $target::class : (string) $target) . '::' . (string) $method;
        }

        if (is_object($callable)) {
            return $callable::class . '::__invoke';
        }

        return 'callable';
    }
}
