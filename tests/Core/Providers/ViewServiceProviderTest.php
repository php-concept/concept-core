<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Path\PathManager;
use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Providers\ViewServiceProvider;
use League\Container\Container;
use League\Container\ReflectionContainer;
use PHPUnit\Framework\TestCase;
use Twig\Extension\StringLoaderExtension;

final class ViewServiceProviderTest extends TestCase
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
        $provider = new ViewServiceProvider();
        self::assertTrue($provider->provides(ViewInterface::class));
        self::assertFalse($provider->provides('view.unknown'));
    }

    public function testRegisterBuildsTwigViewAndSupportsNamespaces(): void
    {
        $container = new Container();
        $container->add(PathManager::class, new PathManager($this->tmpRoot, [
            PathManager::VIEWS_DIR => 'resources/views',
            PathManager::CACHE_DIR => 'storage/cache',
        ]))->setShared(true);

        $container->add(ConfigInterface::class, new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed
            {
                if ($key === 'twig.namespaces') {
                    return ['/components' => 'ui'];
                }
                if ($key === 'twig.extensions') {
                    return [];
                }

                return $default;
            }
            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string { return $default; }
            public function getInt(string $key, int $default = 0): int { return $default; }
            public function getBool(string $key, bool $default = false): bool
            {
                return $key === 'app.debug' ? false : $default;
            }
        })->setShared(true);

        $provider = new ViewServiceProvider();
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
            PathManager::VIEWS_DIR => 'resources/views',
            PathManager::CACHE_DIR => 'storage/cache',
        ]))->setShared(true);

        $container->add(ConfigInterface::class, new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed
            {
                if ($key === 'twig.extensions') {
                    return [
                        StringLoaderExtension::class,
                        'Tests\\Fixtures\\NonExistentTwigExtension',
                        123,
                    ];
                }
                if ($key === 'twig.namespaces') {
                    return [
                        '/components' => 'ui',
                        '/skip' => 99,
                    ];
                }

                return $default;
            }
            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string { return $default; }
            public function getInt(string $key, int $default = 0): int { return $default; }
            public function getBool(string $key, bool $default = false): bool
            {
                return $key === 'app.debug' ? true : $default;
            }
        })->setShared(true);

        $provider = new ViewServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        /** @var ViewInterface $view */
        $view = $container->get(ViewInterface::class);

        self::assertSame('Hello Ada', $view->render('page', ['name' => 'Ada']));
    }

    public function testRegisterAcceptsNonArrayExtensionsAndNamespaces(): void
    {
        $container = new Container();
        $container->add(PathManager::class, new PathManager($this->tmpRoot, [
            PathManager::VIEWS_DIR => 'resources/views',
            PathManager::CACHE_DIR => 'storage/cache',
        ]))->setShared(true);

        $container->add(ConfigInterface::class, new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed
            {
                if ($key === 'twig.extensions') {
                    return 'not-an-array';
                }
                if ($key === 'twig.namespaces') {
                    return 'still-not-an-array';
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

        $provider = new ViewServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        /** @var ViewInterface $view */
        $view = $container->get(ViewInterface::class);

        self::assertSame('Hello Ada', $view->render('page', ['name' => 'Ada']));
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
