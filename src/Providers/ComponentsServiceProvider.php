<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Component\ComponentRegistry;
use Concept\Core\Components\Component\Contracts\ComponentInterface;
use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Database\Registries\MigrationRegistry;
use Concept\Core\Components\Database\Registries\SeederRegistry;
use Concept\Core\Components\View\Registries\ViewExtensionRegistry;
use Concept\Core\Components\View\Registries\ViewPathRegistry;
use Concept\Core\Components\View\Registries\ViewContextRegistry;
use Concept\Core\Events\Framework\ComponentsRegistering;
use Concept\Core\Providers\Concerns\PeeksEventDispatcher;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use League\Container\ServiceProvider\ServiceProviderInterface;
use League\Route\Router;
use Symfony\Component\Console\Application as ConsoleApplication;

class ComponentsServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    use PeeksEventDispatcher;

    public function provides(string $id): bool
    {
        $services = [
            ComponentRegistry::class,
        ];

        return in_array($id, $services);
    }

    public function register(): void
    {
        $container = $this->getContainer();
        $container->add(ComponentRegistry::class, function() use ($container) {
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);
            /** @var class-string<ComponentInterface>[] $componentClasses */
            $componentClasses = $config->get('components');

            return new ComponentRegistry($container, $componentClasses);
        })->setShared(true);
    }

    public function boot(): void
    {
        $this->register();

        $container = $this->getContainer();

        /** @var ComponentRegistry $registry */
        $registry = $container->get(ComponentRegistry::class);
        $this->peekEventDispatcher()?->dispatch(new ComponentsRegistering($registry->all()));

        if (PHP_SAPI === 'cli') {
            $this->registerConsoleCommands($registry);
            $this->registerComponentSeeders($registry);
            $this->registerComponentMigrations($registry);

            return;
        }

        $this->registerComponentProviders($registry);
        $this->registerComponentRoutes($registry);
        $this->registerComponentViewFeatures($registry);
    }

    private function registerComponentRoutes(ComponentRegistry $registry): void
    {
        $container = $this->getContainer();
        $router = $container->get(Router::class);
        foreach ($registry->routes() as $routesFileName) {
            if (file_exists($routesFileName)) {
                require $routesFileName;
            }
        }
    }

    private function registerComponentProviders(ComponentRegistry $registry): void
    {
        foreach ($registry->providers() as $providerClass) {
            /** @var ServiceProviderInterface $provider */
            $provider = new $providerClass();
            $this->getContainer()->addServiceProvider($provider);
        }
    }

    private function registerComponentViewFeatures(ComponentRegistry $registry): void
    {
        $container = $this->getContainer();

        /** @var ViewExtensionRegistry $viewExtensionsRegistry */
        $viewExtensionsRegistry = $container->get(ViewExtensionRegistry::class);
        $viewExtensionsRegistry->append($registry->viewExtensions());

        /** @var ViewPathRegistry $viewPathRegistry */
        $viewPathRegistry = $container->get(ViewPathRegistry::class);
        $viewPathRegistry->append($registry->viewPaths());

        /** @var ViewContextRegistry $viewContextRegistry */
        $viewContextRegistry = $container->get(ViewContextRegistry::class);
        $viewContextRegistry->append($registry->viewContexts());
    }


    private function registerConsoleCommands(ComponentRegistry $registry): void
    {
        $container = $this->getContainer();
        /** @var ConsoleApplication $consoleApplication */
        $consoleApplication = $container->get(ConsoleApplication::class);

        /** @var array<class-string> $commandClasses */
        $commandClasses = $registry->commands();
        foreach ($commandClasses as $commandClassName) {
            /** @var Callable $command */
            $command = $container->get($commandClassName);
            $consoleApplication->addCommand($command);
        }
    }

    private function registerComponentSeeders(ComponentRegistry $registry): void
    {
        /** @var SeederRegistry $seederRegistry */
        $seederRegistry = $this->getContainer()->get(SeederRegistry::class);
        $seederRegistry->append($registry->seeders());
    }

    private function registerComponentMigrations(ComponentRegistry $registry): void
    {
        /** @var MigrationRegistry $migrationRegistry */
        $migrationRegistry = $this->getContainer()->get(MigrationRegistry::class);
        $migrationRegistry->append($registry->migrations());
    }
}
