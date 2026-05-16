<?php declare(strict_types=1);

namespace Concept\Core\Components\Component;

use Concept\Core\Components\Component\Contracts\ComponentInterface;
use Concept\Core\Components\Database\Contracts\SeederInterface;
use Illuminate\Database\Migrations\Migration;
use InvalidArgumentException;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\Console\Command\Command;
use Twig\Extension\ExtensionInterface;

final class ComponentRegistry
{
    private const string ERR_INVALID_COMPONENT = 'Component must implement %s: %s';

    /** @var class-string<ComponentInterface>[] */
    private readonly array $componentClasses;

    /** @var ComponentInterface[]|null */
    private ?array $components = null;

    /**
     * @param class-string<ComponentInterface>[] $componentClasses
     */
    public function __construct(private readonly ContainerInterface $container, array $componentClasses)
    {
        $this->componentClasses = $componentClasses;
    }

    /**
     * @return string[]
     */
    public function routes(): array
    {
        $routes = [];

        foreach ($this->components() as $component) {
            $routeFile = $component->routes();
            if (!is_string($routeFile)) {
                continue;
            }

            if (!file_exists($routeFile)) {
                throw new InvalidArgumentException(sprintf('Component routes file not found: %s', $routeFile));
            }

            $routes[] = $routeFile;
        }

        return $routes;
    }

    /**
     * @return class-string<ServiceProviderInterface>[]
     */
    public function providers(): array
    {
        $providers = [];
        foreach ($this->components() as $component) {
            $providers = array_merge($providers, $component->providers());
        }

        return $providers;
    }

    /**
     * @return class-string<SeederInterface>[]
     */
    public function seeders(): array
    {
        $seeders = [];

        foreach ($this->components() as $component) {
            $seeders = array_merge($seeders, $component->seeders());
        }

        return $seeders;
    }

    /**
     * @return class-string<Migration>[]
     */
    public function migrations(): array
    {
        $migrations = [];

        foreach ($this->components() as $component) {
            $migrations = array_merge($migrations, $component->migrations());
        }

        return $migrations;
    }

    /**
     * @return class-string<Command>[]
     */
    public function commands(): array
    {
        $commands = [];

        foreach ($this->components() as $component) {
            $commands = array_merge($commands, $component->commands());
        }

        return $commands;
    }

    /**
     * @return class-string<ExtensionInterface>[]
     */
    public function viewExtensions(): array
    {
        $extensions = [];

        foreach ($this->components() as $component) {
            $extensions = array_merge($extensions, $component->viewExtensions());
        }

        return $extensions;
    }

    /**
     * @return array<string, string>
     */
    public function viewPaths(): array
    {
        $namespaces = [];

        foreach ($this->components() as $component) {
            $namespaces = array_merge($namespaces, $component->viewPaths());
        }

        return $namespaces;
    }

    /**
     * @return array<string, string>
     */
    public function viewContexts(): array
    {
        $map = [];

        foreach ($this->components() as $component) {
            $map = array_merge($map, $component->viewContexts());
        }

        return $map;
    }

    /**
     * @return ComponentInterface[]
     */
    public function all(): array
    {
        return $this->components();
    }

    /**
     * @return ComponentInterface[]
     */
    private function components(): array
    {
        if ($this->components !== null) {
            return $this->components;
        }

        $this->components = [];

        foreach ($this->componentClasses as $componentClass) {
            $component = $this->container->get($componentClass);

            if (!$component instanceof ComponentInterface) {
                throw new InvalidArgumentException(
                    sprintf(self::ERR_INVALID_COMPONENT, ComponentInterface::class, $componentClass)
                );
            }

            $this->components[] = $component;
        }

        return $this->components;
    }
}
