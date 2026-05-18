<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Components\Path\PathManager;
use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\View\Registries\ViewExtensionRegistry;
use Concept\Core\Components\View\Registries\ViewContextRegistry;
use Concept\Core\Components\View\Registries\ViewPathRegistry;
use Concept\Core\Components\View\View;
use Concept\Core\Providers\Concerns\PeeksEventDispatcher;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Extension\DebugExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Extension\ProfilerExtension;
use Twig\Loader\FilesystemLoader;
use Twig\Profiler\Profile;

class ViewServiceProvider extends AbstractServiceProvider
{
    use PeeksEventDispatcher;

    public function provides(string $id): bool
    {
        $services = [
            ViewInterface::class,
            ViewExtensionRegistry::class,
            ViewPathRegistry::class,
            ViewContextRegistry::class,
        ];

        return in_array($id, $services);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(ViewInterface::class, function () use ($container) {
            /** @var PathManager $pathManager */
            $pathManager = $container->get(PathManager::class);
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            $debug = $config->getBool('app.debug');
            $templatesPath = $pathManager->get(PathManager::VIEWS_DIR);

            $cacheSubDir = $config->getString('view.cache_dir', 'views');
            $cachePath = $pathManager->get(PathManager::CACHE_DIR, $cacheSubDir);

            $loader = new FilesystemLoader($templatesPath);
            $twig = new Environment($loader, [
                'cache' => $debug ? false : $cachePath,
                'debug' => $debug,
            ]);

            /** @var ViewExtensionRegistry $viewExtensionRegistry */
            $viewExtensionRegistry = $container->get(ViewExtensionRegistry::class);
            $this->addExtensions($twig, $viewExtensionRegistry->all(), $debug);

            /** @var ViewPathRegistry $viewPathRegistry */
            $viewPathRegistry = $container->get(ViewPathRegistry::class);
            $this->addPaths($loader, $pathManager->root(), $viewPathRegistry->all());

            $this->addFallbackPath($loader, $templatesPath);

            $profile = null;
            if ($debug) {
                $profile = new Profile();
                $twig->addExtension(new ProfilerExtension($profile));
            }

            return new View($twig, $this->peekEventDispatcher(), $profile);
        })->setShared(true);

        $container->add(ViewExtensionRegistry::class, function () use ($container) {
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            /** @var array<string> $extensions */
            $extensions = $config->get('view.extensions', []);
            $viewExtensionRegistry = new ViewExtensionRegistry();
            $viewExtensionRegistry->append($extensions);

            return $viewExtensionRegistry;
        })->setShared(true);

        $container->add(ViewPathRegistry::class, function () use ($container) {
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            /** @var array<string, string> $viewPaths */
            $viewPaths = $config->get('view.paths', []);
            $viewPathRegistry = new ViewPathRegistry();
            $viewPathRegistry->append($viewPaths);

            return $viewPathRegistry;
        })->setShared(true);

        $container->add(ViewContextRegistry::class, function () use ($container) {
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            /** @var array<string> $viewContexts */
            $viewContexts = $config->get('view.contexts', []);
            $viewContextsRegistry = new ViewContextRegistry();
            $viewContextsRegistry->append($viewContexts);

            return $viewContextsRegistry;
        })->setShared(true);
    }

    /**
     * @param Environment $twig
     * @param array<string> $extensions
     * @param bool $debug
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function addExtensions(Environment $twig, array $extensions, bool $debug): void
    {
        if ($debug) {
            $twig->addExtension(new DebugExtension());
        }

        foreach ($extensions as $extensionClass) {
            /** @var ExtensionInterface $extension */
            $extension = $this->getContainer()->get($extensionClass);
            $twig->addExtension($extension);
        }
    }

    /**
     * @param FilesystemLoader $loader
     * @param string $rootPath
     * @param array<string> $namespaces
     * @return void
     * @throws LoaderError
     */
    private function addPaths(FilesystemLoader $loader, string $rootPath, array $namespaces): void
    {
        if ($namespaces) {
            foreach ($namespaces as $namespace => $path) {
                $loader->addPath(rtrim($rootPath, '/') . '/' . ltrim($path, '/'), $namespace);
            }
        }
    }

    /**
     * @param FilesystemLoader $loader
     * @param string $templatesPath
     * @return void
     * @throws LoaderError
     */
    private function addFallbackPath(FilesystemLoader $loader, string $templatesPath): void
    {
       // add root views as fallback
        $loader->addPath($templatesPath);
    }
}
