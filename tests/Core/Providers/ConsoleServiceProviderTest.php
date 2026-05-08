<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Path\PathManager;
use Concept\Core\Console\Commands\DbMigrationListCommand;
use Concept\Core\Providers\ConsoleServiceProvider;
use League\Container\Container;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;

final class ConsoleServiceProviderTest extends TestCase
{
    private string $tmpRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpRoot = sys_get_temp_dir() . '/provider-console-' . bin2hex(random_bytes(6));
        mkdir($this->tmpRoot . '/bootstrap', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->tmpRoot);
        parent::tearDown();
    }

    public function testProvidesApplicationService(): void
    {
        $provider = new ConsoleServiceProvider();

        self::assertTrue($provider->provides(Application::class));
        self::assertFalse($provider->provides('console.unknown'));
    }

    public function testRegisterBuildsApplicationWithContainerLoader(): void
    {
        file_put_contents($this->tmpRoot . '/bootstrap/commands.php', '<?php return [\\Concept\\Core\\Console\\Commands\\DbMigrationListCommand::class];');

        $container = new Container();
        $container->add(PathManager::class, new PathManager($this->tmpRoot, [
            PathManager::BOOTSTRAP_DIR => 'bootstrap',
        ]))->setShared(true);

        $container->add(ConfigInterface::class, new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed { return $default; }
            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string
            {
                return match ($key) {
                    'app.name' => 'ConsoleApp',
                    'app.version' => '2.0.1',
                    default => $default,
                };
            }
            public function getInt(string $key, int $default = 0): int { return $default; }
            public function getBool(string $key, bool $default = false): bool { return $default; }
        })->setShared(true);

        $provider = new ConsoleServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        /** @var Application $app */
        $app = $container->get(Application::class);
        self::assertSame('ConsoleApp', $app->getName());
        self::assertSame('2.0.1', $app->getVersion());

        $ref = new \ReflectionClass($app);
        $prop = $ref->getProperty('commandLoader');
        $loader = $prop->getValue($app);
        self::assertInstanceOf(ContainerCommandLoader::class, $loader);
    }

    public function testRegisterThrowsForCommandClassWithoutAsCommandAttribute(): void
    {
        file_put_contents($this->tmpRoot . '/bootstrap/commands.php', '<?php return [\\stdClass::class];');

        $container = new Container();
        $container->add(PathManager::class, new PathManager($this->tmpRoot, [
            PathManager::BOOTSTRAP_DIR => 'bootstrap',
        ]))->setShared(true);

        $provider = new ConsoleServiceProvider();
        $provider->setContainer($container);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Command class must have #[AsCommand] attribute');

        $provider->register();
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
