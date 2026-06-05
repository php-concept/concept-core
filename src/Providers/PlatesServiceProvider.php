<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Path\PathManager;
use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Components\View\PlatesView;
use Concept\Core\Components\View\Registries\ViewRegistry;
use Concept\Core\Events\Framework\ServiceAwakening;
use Concept\Core\Providers\Concerns\PeeksEventDispatcher;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;

class PlatesServiceProvider extends AbstractServiceProvider
{
    public const string DEFAULT_EXTENSION = '.php';

    use PeeksEventDispatcher;

    public function provides(string $id): bool
    {
        $services = [
            ViewInterface::class,
        ];

        return in_array($id, $services);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(ViewInterface::class, function () use ($container) {
            $this->peekEventDispatcher()?->dispatch(new ServiceAwakening(ViewInterface::class));

            /** @var PathManager $pathManager */
            $pathManager = $container->get(PathManager::class);
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            $templatesPath = $pathManager->get(PathManager::VIEWS_DIR);
            $defaultExtension = $config->getString('view.default_extension', self::DEFAULT_EXTENSION);
            $engine = new Engine($templatesPath, ltrim($defaultExtension, '.'));

            /** @var ViewRegistry $viewRegistry */
            $viewRegistry = $container->get(ViewRegistry::class);
            $this->addExtensions($engine, $viewRegistry->extensions()->all());
            $this->addFolders($engine, $pathManager->root(), $viewRegistry->paths()->all());

            return new PlatesView($engine, $this->peekEventDispatcher());
        })->setShared(true);
    }

    /**
     * @param Engine $engine
     * @param array<string> $extensions
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function addExtensions(Engine $engine, array $extensions): void
    {
        foreach ($extensions as $extensionClass) {
            /** @var ExtensionInterface $extension */
            $extension = $this->getContainer()->get($extensionClass);
            $engine->loadExtension($extension);
        }
    }

    /**
     * @param Engine $engine
     * @param string $rootPath
     * @param array<string, string> $namespaces
     * @return void
     */
    private function addFolders(Engine $engine, string $rootPath, array $namespaces): void
    {
        foreach ($namespaces as $namespace => $path) {
            $engine->addFolder(
                $namespace,
                rtrim($rootPath, '/') . '/' . ltrim($path, '/'),
                true
            );
        }
    }
}
