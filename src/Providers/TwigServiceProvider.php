<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Foundation\PathManager;
use Concept\Core\Foundation\PathName;
use Concept\Core\Components\Telemetry\TelemetryEvent;
use Concept\Core\Components\Telemetry\TelemetryTrait;
use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Components\View\Registries\ViewRegistry;
use Concept\Core\Components\View\TwigView;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Twig\Environment;
use Twig\Error\LoaderError;
use Twig\Extension\DebugExtension;
use Twig\Extension\ExtensionInterface;
use Twig\Loader\FilesystemLoader;

class TwigServiceProvider extends AbstractServiceProvider
{
    use TelemetryTrait;

    public const string DEFAULT_EXTENSION = '.twig';

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
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, ViewInterface::class);

            /** @var PathManager $pathManager */
            $pathManager = $container->get(PathManager::class);
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            $debug = $config->getBool(ConfigKey::APP_DEBUG);
            $templatesPath = $pathManager->get(PathName::VIEWS);

            $cacheSubDir = $config->getString(ConfigKey::VIEW_CACHE_DIR, 'views');
            $cachePath = $pathManager->get(PathName::CACHE, $cacheSubDir);

            $loader = new FilesystemLoader($templatesPath);
            $twig = new Environment($loader, [
                'cache' => $debug ? false : $cachePath,
                'debug' => $debug,
            ]);

            /** @var ViewRegistry $viewRegistry */
            $viewRegistry = $container->get(ViewRegistry::class);
            $this->addExtensions($twig, $viewRegistry->extensions()->all(), $debug);
            $this->addPaths($loader, $pathManager->root(), $viewRegistry->paths()->all());

            $this->addFallbackPath($loader, $templatesPath);
            $defaultExtension = $config->getString(ConfigKey::VIEW_DEFAULT_EXTENSION, self::DEFAULT_EXTENSION);

            return new TwigView($twig, $defaultExtension, $this->telemetry());
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
