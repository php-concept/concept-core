<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\View\Registries\ViewExtensionRegistry;
use Concept\Core\Components\View\Registries\ViewContextRegistry;
use Concept\Core\Components\View\Registries\ViewPathRegistry;
use Concept\Core\Components\View\Registries\ViewRegistry;
use Concept\Core\Events\Framework\ServiceAwakening;
use Concept\Core\Providers\Concerns\PeeksEventDispatcher;
use League\Container\ServiceProvider\AbstractServiceProvider;


class ViewRegistryServiceProvider extends AbstractServiceProvider
{
    use PeeksEventDispatcher;

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
            $this->peekEventDispatcher()?->dispatch(new ServiceAwakening(ViewRegistry::class));

            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            /** @var array<string, string> $viewPaths */
            $viewPaths = $config->get('view.paths', []);
            $viewPathRegistry = new ViewPathRegistry();
            $viewPathRegistry->append($viewPaths);

            /** @var array<string> $extensions */
            $extensions = $config->get('view.extensions', []);
            $viewExtensionRegistry = new ViewExtensionRegistry();
            $viewExtensionRegistry->append($extensions);

            /** @var array<string> $viewContexts */
            $viewContexts = $config->get('view.contexts', []);
            $viewContextsRegistry = new ViewContextRegistry();
            $viewContextsRegistry->append($viewContexts);

            return new ViewRegistry($viewPathRegistry, $viewExtensionRegistry, $viewContextsRegistry);
        })->setShared(true);
    }
}
