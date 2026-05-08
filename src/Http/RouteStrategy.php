<?php declare(strict_types=1);

namespace Concept\Core\Http;

use Closure;
use Concept\Core\Components\Caster\Contracts\CasterInterface;
use Concept\Core\Components\Caster\Exceptions\CastingException;
use Concept\Core\Http\Requests\FormRequestInterface;
use Concept\Core\Components\Validator\Exceptions\ValidationException;
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
    public function invokeRouteCallable(Route $route, ServerRequestInterface $request): ResponseInterface
    {
        // 1. Prepare the request with route variables
        $request = $this->prepareRequest($route, $request);

        // 2. Get the reflection object for the callable
        $callable = $route->getCallable($this->getContainer());
        /** @var ReflectionMethod $reflection */
        $reflection = $this->getReflection($callable);

        // 3. Resolve arguments for the reflection object (Auto-wiring)
        $arguments = $this->resolveArguments($reflection, $request, $route->getVars());

        // 4. Invoke the callable with prepared arguments
        if (is_array($callable)) {
            // For class methods, we need to pass the object instance as the first argument
            /** @var object $object */
            $object = $callable[0];
            /** @var ResponseInterface $callableObj */
            $callableObj = $reflection->invokeArgs($object, $arguments);

            return $callableObj;
        }

        // 5. Invokable objects (__invoke) require target object for ReflectionMethod invocation.
        if (is_object($callable) && !($callable instanceof Closure)) {
            /** @var ResponseInterface $callableObj */
            $callableObj = $reflection->invokeArgs($callable, $arguments);

            return $callableObj;
        }

        // For closures and regular functions, we only pass the arguments.
        /** @phpstan-ignore-next-line */
        return $reflection->invokeArgs($arguments);
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
        if (is_array($callable) && isset($callable[0], $callable[1])) {
            /** @var object|string $classOrObject */
            $classOrObject = $callable[0];
            /** @var string $methodName */
            $methodName = $callable[1];

            return new ReflectionMethod($classOrObject, $methodName);
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

        if (!$formRequest->validate()) {
            throw new ValidationException($formRequest->errors(), $formRequest->all());
        }

        return $formRequest;
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
}
