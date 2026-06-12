<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Services\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Foundation\PathManager;
use Concept\Core\Foundation\PathName;
use Concept\Core\Services\View\Contracts\ViewInterface;
use Concept\Core\Services\View\Registries\ViewContextRegistry;
use Concept\Core\Services\View\Registries\ViewExtensionRegistry;
use Concept\Core\Services\View\Registries\ViewPathRegistry;
use Concept\Core\Services\View\Registries\ViewRegistry;
use Concept\Core\Providers\View\TwigServiceProvider;
use Concept\Core\Providers\View\ViewRegistryServiceProvider;
use League\Container\Container;
use League\Container\ReflectionContainer;
use PHPUnit\Framework\TestCase;
use Twig\Extension\StringLoaderExtension;

final class TwigServiceProviderTest extends TestCase
{
    private string $tmpRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpRoot = sys_get_temp_dir() . '/provider-view-' . bin2hex(random_bytes(6));
        mkdir($this->tmpRoot . '/resources/views/components', 0777, true);
        mkdir($this->tmpRoot . '/storage/cache/views', 0777, true);

        file_put_contents($this->tmpRoot . '/resources/views/page.twig', 'Hello {{ name }}');
        file_put_contents($this->tmpRoot . '/resources/views/components/badge.twig', '[{{ label }}]');
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->tmpRoot);
        parent::tearDown();
    }

    public function testProvidesViewInterface(): void
    {
        $provider = new TwigServiceProvider();
        self::assertTrue($provider->provides(ViewInterface::class));
        self::assertFalse($provider->provides('view.unknown'));
    }

    public function testRegisterBuildsTwigViewAndSupportsNamespaces(): void
    {
        $container = new Container();
        $container->add(PathManager::class, new PathManager($this->tmpRoot, [
            PathName::VIEWS => 'resources/views',
            PathName::CACHE => 'storage/cache',
        ]))->setShared(true);

        $container->add(ViewRegistry::class, $this->makeViewRegistry([
            'ui' => 'resources/views/components',
        ]))->setShared(true);
        $container->add(ConfigInterface::class, $this->makeConfig(debug: false))->setShared(true);

        $provider = new TwigServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        /** @var ViewInterface $view */
        $view = $container->get(ViewInterface::class);

        self::assertSame('Hello Ada', $view->render('page', ['name' => 'Ada']));
        self::assertSame('[PRO]', $view->render('@ui/badge', ['label' => 'PRO']));
    }

    public function testRegisterEnablesDebugAndConfiguredTwigExtensions(): void
    {
        $container = new Container();
        $container->delegate(new ReflectionContainer());
        $container->add(PathManager::class, new PathManager($this->tmpRoot, [
            PathName::VIEWS => 'resources/views',
            PathName::CACHE => 'storage/cache',
        ]))->setShared(true);

        $container->add(ViewRegistry::class, $this->makeViewRegistry(
            ['ui' => 'resources/views/components'],
            [StringLoaderExtension::class],
        ))->setShared(true);
        $container->add(ConfigInterface::class, $this->makeConfig(debug: true))->setShared(true);

        $provider = new TwigServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        /** @var ViewInterface $view */
        $view = $container->get(ViewInterface::class);

        self::assertSame('Hello Ada', $view->render('page', ['name' => 'Ada']));
    }

    public function testRegisterThrowsWhenTwigExtensionsConfigIsNotArray(): void
    {
        $container = new Container();
        $container->add(PathManager::class, new PathManager($this->tmpRoot, [
            PathName::VIEWS => 'resources/views',
            PathName::CACHE => 'storage/cache',
        ]))->setShared(true);

        $container->add(ConfigInterface::class, new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed
            {
                if ($key === ConfigKey::VIEW_EXTENSIONS) {
                    return 'not-an-array';
                }

                return $default;
            }
            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string { return $default; }
            public function getInt(string $key, int $default = 0): int { return $default; }
            public function getBool(string $key, bool $default = false): bool { return false; }
        })->setShared(true);

        $registryProvider = new ViewRegistryServiceProvider();
        $registryProvider->setContainer($container);
        $container->addServiceProvider($registryProvider);

        $provider = new TwigServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        $this->expectException(\TypeError::class);

        $container->get(ViewInterface::class);
    }

    /**
     * @param array<string, string> $paths
     * @param array<string> $extensions
     */
    private function makeViewRegistry(array $paths = [], array $extensions = []): ViewRegistry
    {
        $viewPathRegistry = new ViewPathRegistry();
        $viewPathRegistry->append($paths);

        $viewExtensionRegistry = new ViewExtensionRegistry();
        $viewExtensionRegistry->append($extensions);

        return new ViewRegistry(
            $viewPathRegistry,
            $viewExtensionRegistry,
            new ViewContextRegistry(),
        );
    }

    private function makeConfig(bool $debug): ConfigInterface
    {
        return new class ($debug) implements ConfigInterface {
            public function __construct(private readonly bool $debug) {}

            public function get(string $key, mixed $default = null): mixed
            {
                return $default;
            }

            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string { return $default; }
            public function getInt(string $key, int $default = 0): int { return $default; }
            public function getBool(string $key, bool $default = false): bool
            {
                return $key === ConfigKey::APP_DEBUG ? $this->debug : $default;
            }
        };
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }
        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }
            $full = $path . '/' . $item;
            if (is_dir($full)) {
                $this->removeTree($full);
            } else {
                unlink($full);
            }
        }
        rmdir($path);
    }
}
