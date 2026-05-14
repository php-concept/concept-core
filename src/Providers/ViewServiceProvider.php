<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Components\Path\PathManager;
use Concept\Core\Components\Config\Contracts\ConfigInterface;
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

            $extensions = $config->get('twig.extensions', []);
            $this->addAppExtensions($twig, $extensions, $debug);

            $this->addNamespaces($loader, $config, $pathManager->root(), $templatesPath);

            return new View($twig);
        })->setShared(true);
    }

    /**
     * @param Environment $twig
     * @param mixed $extensions
     * @param bool $debug
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function addAppExtensions(Environment $twig, $extensions, bool $debug): void
    {
        if ($debug) {
            $twig->addExtension(new DebugExtension());
        }

        if (!is_array($extensions)) {
            return;
        }

        foreach ($extensions as $extensionClass) {
            /** @var ExtensionInterface $extension */
            if (is_string($extensionClass) && class_exists($extensionClass)) {
                /** @var ExtensionInterface $extension */
                $extension = $this->getContainer()->get($extensionClass);
                $twig->addExtension($extension);
            }
        }
    }

    /**
     * @param FilesystemLoader $loader
     * @param ConfigInterface $config
     * @param string $rootPath
     * @param string $templatesPath
     * @return void
     * @throws LoaderError
     */
    private function addNamespaces(
        FilesystemLoader $loader,
        ConfigInterface $config,
        string $rootPath,
        string $templatesPath): void
    {
        $namespaces = $config->get('twig.namespaces', []);
        if (is_array($namespaces)) {
            foreach ($namespaces as $namespace => $path) {
                if (!is_string($namespace) || !is_string($path)) {
                    continue;
                }
                $loader->addPath(rtrim($rootPath, '/') . '/' . ltrim($path, '/'), $namespace);
            }
        }

        // add root views as fallback
        $loader->addPath($templatesPath);
    }
}
