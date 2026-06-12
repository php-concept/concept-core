<?php declare(strict_types=1);

namespace Concept\Core\Providers\Component;

use Concept\Core\Services\Component\ComponentRegistry;
use Concept\Core\Services\Component\Contracts\ComponentInterface;
use Concept\Core\Services\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Services\Database\Registries\MigrationRegistry;
use Concept\Core\Services\Database\Registries\SeederRegistry;
use Concept\Core\Services\Telemetry\TelemetryEvent;
use Concept\Core\Services\Telemetry\TelemetryTrait;
use Concept\Core\Services\View\Registries\ViewRegistry;
use Concept\Core\Foundation\PhpSapi;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use League\Container\ServiceProvider\ServiceProviderInterface;
use Concept\Core\Http\Routing\Contracts\RouterInterface;
use Symfony\Component\Console\Application as ConsoleApplication;

class ComponentsServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    use TelemetryTrait;

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
            $componentClasses = $config->get(ConfigKey::COMPONENTS);

            return new ComponentRegistry($container, $componentClasses);
        })->setShared(true);
    }

    public function boot(): void
    {
        $this->register();

        $container = $this->getContainer();

        /** @var ComponentRegistry $registry */
        $registry = $container->get(ComponentRegistry::class);
        foreach ($registry->all() as $component) {
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_COMPONENT_REGISTERED, $component::class);
        }

        $this->registerConsoleCommands($registry);
        $this->registerComponentSeeders($registry);
        $this->registerComponentMigrations($registry);

        $this->registerComponentProviders($registry);
        $this->registerComponentRoutes($registry);
        if (PHP_SAPI !== PhpSapi::CLI) {
            $this->registerComponentViewFeatures($registry);
        }
    }

    private function registerComponentRoutes(ComponentRegistry $registry): void
    {
        $container = $this->getContainer();
        $router = $container->get(RouterInterface::class);
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

        /** @var ViewRegistry $viewRegistry */
        $viewRegistry = $container->get(ViewRegistry::class);
        $viewRegistry->extensions()->append($registry->viewExtensions());
        $viewRegistry->paths()->append($registry->viewPaths());
        $viewRegistry->contexts()->append($registry->viewContexts());
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
