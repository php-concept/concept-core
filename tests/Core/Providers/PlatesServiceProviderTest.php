<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\PathManager;
use Concept\Core\Foundation\PathName;
use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Components\View\Registries\ViewExtensionRegistry;
use Concept\Core\Components\View\Registries\ViewContextRegistry;
use Concept\Core\Components\View\Registries\ViewPathRegistry;
use Concept\Core\Components\View\Registries\ViewRegistry;
use Concept\Core\Providers\PlatesServiceProvider;
use Concept\Core\Providers\ViewRegistryServiceProvider;
use League\Container\Container;
use League\Container\ReflectionContainer;
use League\Plates\Engine;
use League\Plates\Extension\ExtensionInterface;
use PHPUnit\Framework\TestCase;

final class PlatesServiceProviderTest extends TestCase
{
    private string $tmpRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpRoot = sys_get_temp_dir() . '/provider-plates-' . bin2hex(random_bytes(6));
        mkdir($this->tmpRoot . '/resources/views/components', 0777, true);

        file_put_contents($this->tmpRoot . '/resources/views/page.php', 'Hello <?= $this->e($name) ?>');
        file_put_contents($this->tmpRoot . '/resources/views/components/badge.php', '[<?= $this->e($label) ?>]');
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->tmpRoot);
        parent::tearDown();
    }

    public function testProvidesViewInterface(): void
    {
        $provider = new PlatesServiceProvider();

        self::assertTrue($provider->provides(ViewInterface::class));
        self::assertFalse($provider->provides('view.unknown'));
    }

    public function testRegisterBuildsPlatesViewAndSupportsNamespaces(): void
    {
        $container = new Container();
        $container->add(PathManager::class, new PathManager($this->tmpRoot, [
            PathName::VIEWS => 'resources/views',
        ]))->setShared(true);
        $container->add(ViewRegistry::class, $this->makeViewRegistry([
            'ui' => 'resources/views/components',
        ]))->setShared(true);
        $container->add(ConfigInterface::class, $this->makeConfig(debug: false))->setShared(true);

        $provider = new PlatesServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        /** @var ViewInterface $view */
        $view = $container->get(ViewInterface::class);

        self::assertSame('Hello Ada', $view->render('page', ['name' => 'Ada']));
        self::assertSame('[PRO]', $view->render('ui::badge', ['label' => 'PRO']));
    }

    public function testRegisterLoadsConfiguredPlatesExtensions(): void
    {
        $container = new Container();
        $container->delegate(new ReflectionContainer());
        $container->add(PathManager::class, new PathManager($this->tmpRoot, [
            PathName::VIEWS => 'resources/views',
        ]))->setShared(true);
        $container->add(ViewRegistry::class, $this->makeViewRegistry([], [UppercasePlatesExtension::class]))->setShared(true);
        $container->add(ConfigInterface::class, $this->makeConfig(debug: false))->setShared(true);

        file_put_contents($this->tmpRoot . '/resources/views/greet.php', '<?= $this->upper($name) ?>');

        $provider = new PlatesServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        /** @var ViewInterface $view */
        $view = $container->get(ViewInterface::class);

        self::assertSame('ADA', $view->render('greet', ['name' => 'Ada']));
    }

    public function testRegisterThrowsWhenExtensionsConfigIsNotArray(): void
    {
        $container = new Container();
        $container->add(PathManager::class, new PathManager($this->tmpRoot, [
            PathName::VIEWS => 'resources/views',
        ]))->setShared(true);
        $container->add(ConfigInterface::class, new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed
            {
                if ($key === 'view.extensions') {
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

        $provider = new PlatesServiceProvider();
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
                return $key === 'app.debug' ? $this->debug : $default;
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

final class UppercasePlatesExtension implements ExtensionInterface
{
    public function register(Engine $engine): void
    {
        $engine->registerFunction('upper', static fn (string $value): string => strtoupper($value));
    }
}
