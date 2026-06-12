<?php declare(strict_types=1);

namespace Concept\Core\Providers\View;

use Concept\Core\Services\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Services\Telemetry\TelemetryEvent;
use Concept\Core\Services\Telemetry\TelemetryTrait;
use Concept\Core\Services\View\Registries\ViewContextRegistry;
use Concept\Core\Services\View\Registries\ViewExtensionRegistry;
use Concept\Core\Services\View\Registries\ViewPathRegistry;
use Concept\Core\Services\View\Registries\ViewRegistry;
use League\Container\ServiceProvider\AbstractServiceProvider;


class ViewRegistryServiceProvider extends AbstractServiceProvider
{
    use TelemetryTrait;

    public function provides(string $id): bool
    {
        $services = [
            ViewRegistry::class,
        ];

        return in_array($id, $services);
    }

    public function register(): void
    {
        $container = $this->getContainer();
        $container->add(ViewRegistry::class, function () use ($container) {
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, ViewRegistry::class);

            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            /** @var array<string, string> $viewPaths */
            $viewPaths = $config->get(ConfigKey::VIEW_PATHS, []);
            $viewPathRegistry = new ViewPathRegistry();
            $viewPathRegistry->append($viewPaths);

            /** @var array<string> $extensions */
            $extensions = $config->get(ConfigKey::VIEW_EXTENSIONS, []);
            $viewExtensionRegistry = new ViewExtensionRegistry();
            $viewExtensionRegistry->append($extensions);

            /** @var array<string> $viewContexts */
            $viewContexts = $config->get(ConfigKey::VIEW_CONTEXTS, []);
            $viewContextsRegistry = new ViewContextRegistry();
            $viewContextsRegistry->append($viewContexts);

            return new ViewRegistry($viewPathRegistry, $viewExtensionRegistry, $viewContextsRegistry);
        })->setShared(true);
    }
}
