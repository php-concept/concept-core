<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Components\Path\PathManager;
use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\View\Registries\TwigExtensionRegistry;
use Concept\Core\Components\View\Registries\TwigRouteNamespaceRegistry;
use Concept\Core\Components\View\Registries\TwigNamespaceRegistry;
use Concept\Core\Components\View\View;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Extension\DebugExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\FilesystemLoader;

class ViewServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        $services = [
            ViewInterface::class,
            TwigExtensionRegistry::class,
            TwigNamespaceRegistry::class,
            TwigRouteNamespaceRegistry::class,
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

            $cacheSubDir = $config->getString('twig.cache_dir', 'views');
            $cachePath = $pathManager->get(PathManager::CACHE_DIR, $cacheSubDir);

            $loader = new FilesystemLoader($templatesPath);
            $twig = new Environment($loader, [
                'cache' => $debug ? false : $cachePath,
                'debug' => $debug,
            ]);

            /** @var TwigExtensionRegistry $twigExtensionRegistry */
            $twigExtensionRegistry = $container->get(TwigExtensionRegistry::class);
            $this->addExtensions($twig, $twigExtensionRegistry->all(), $debug);

            /** @var TwigNamespaceRegistry $twigNamespaceRegistry */
            $twigNamespaceRegistry = $container->get(TwigNamespaceRegistry::class);
            $this->addNamespaces($loader, $pathManager->root(), $twigNamespaceRegistry->all());

            $this->addFallbackNamespace($loader, $templatesPath);

            return new View($twig);
        })->setShared(true);

        $container->add(TwigExtensionRegistry::class, function () use ($container) {
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            /** @var array<string> $namespaces */
            $namespaces = $config->get('twig.extensions', []);
            $viewExtensionRegistry = new TwigExtensionRegistry();
            $viewExtensionRegistry->append($namespaces);

            return $viewExtensionRegistry;
        })->setShared(true);

        $container->add(TwigNamespaceRegistry::class, function () use ($container) {
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            /** @var array<string> $namespaces */
            $namespaces = $config->get('twig.namespaces', []);
            $viewNamespaceRegistry = new TwigNamespaceRegistry();
            $viewNamespaceRegistry->append($namespaces);

            return $viewNamespaceRegistry;
        })->setShared(true);

        $container->add(TwigRouteNamespaceRegistry::class, function () use ($container) {
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            /** @var array<string> $routeNamespaces */
            $routeNamespaces = $config->get('twig.route_namespace', []);
            $viewRouteNamespaceRegistry = new TwigRouteNamespaceRegistry();
            $viewRouteNamespaceRegistry->append($routeNamespaces);

            return $viewRouteNamespaceRegistry;
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
            if (class_exists($extensionClass)) {
                /** @var ExtensionInterface $extension */
                $extension = $this->getContainer()->get($extensionClass);
                $twig->addExtension($extension);
            }
        }
    }

    /**
     * @param FilesystemLoader $loader
     * @param string $rootPath
     * @param array<string> $namespaces
     * @return void
     * @throws LoaderError
     */
    private function addNamespaces(FilesystemLoader $loader, string $rootPath, array $namespaces): void
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
    private function addFallbackNamespace(FilesystemLoader $loader, string $templatesPath): void
    {
       // add root views as fallback
        $loader->addPath($templatesPath);
    }
}
