<?php declare(strict_types=1);

namespace Concept\Core\Console\Commands;

use Closure;
use Laravel\SerializableClosure\SerializableClosure;
use League\Route\Route;
use Concept\Core\Http\Routing\Contracts\RouterInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use ReflectionClass;
use ReflectionProperty;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class RouteListCommand extends Command
{
    private const string COMMAND_NAME = 'route:list';
    private const string COMMAND_DESCRIPTION = 'List all registered application routes';
    private const string OPTION_FULL_MIDDLEWARE = 'full-middleware';
    private const string OPTION_FULL_MIDDLEWARE_SHORTCUT = 'F';
    private const string OPTION_FULL_MIDDLEWARE_DESCRIPTION = 'Display middleware with full class names';
    private const string MSG_TITLE = 'Application Routes';
    private const string MSG_NOT_FOUND = 'No routes found.';
    private const string MSG_TOTAL = 'Total: %d route(s).';

    public function __construct(private readonly RouterInterface $router)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION)
            ->addOption(
                self::OPTION_FULL_MIDDLEWARE,
                self::OPTION_FULL_MIDDLEWARE_SHORTCUT,
                InputOption::VALUE_NONE,
                self::OPTION_FULL_MIDDLEWARE_DESCRIPTION
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(self::MSG_TITLE);

        $routes = $this->resolveRoutes();
        if ($routes === []) {
            $io->warning(self::MSG_NOT_FOUND);

            return Command::SUCCESS;
        }

        usort(
            $routes,
            static fn(Route $left, Route $right): int => [$left->getPath(), $left->getMethod()] <=>
                [$right->getPath(), $right->getMethod()]
        );

        $fullMiddlewareClass = (bool) $input->getOption(self::OPTION_FULL_MIDDLEWARE);

        $io->table(
            ['Method', 'URI', 'Name', 'Action', 'Middleware'],
            array_map(
                fn(Route $route): array => [
                    $this->formatMethods($route),
                    $route->getPath(),
                    $route->getName() ?? '',
                    $this->describeAction($route),
                    $this->describeMiddleware($route, $fullMiddlewareClass),
                ],
                $routes
            )
        );

        $io->success(sprintf(self::MSG_TOTAL, count($routes)));

        return Command::SUCCESS;
    }

    /**
     * @return list<Route>
     */
    private function resolveRoutes(): array
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
     * @param ReflectionClass<RouterInterface> $reflection
     */
    private function readRouterProperty(ReflectionClass $reflection, string $propertyName): mixed
    {
        $property = $reflection->getProperty($propertyName);
        $property->setAccessible(true);

        return $property->getValue($this->router);
    }

    private function formatMethods(Route $route): string
    {
        $methods = $route->getMethod();

        if (is_string($methods)) {
            return $methods;
        }

        return implode('|', $methods);
    }

    private function describeAction(Route $route): string
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

    private function describeMiddleware(Route $route, bool $fullClassName): string
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

        if ($middleware === []) {
            return '';
        }

        return implode(', ', $middleware);
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
