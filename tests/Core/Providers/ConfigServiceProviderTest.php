<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Path\PathManager;
use Concept\Core\Providers\ConfigServiceProvider;
use League\Container\Container;
use PHPUnit\Framework\TestCase;

final class ConfigServiceProviderTest extends TestCase
{
    private string $tmpRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpRoot = sys_get_temp_dir() . '/provider-config-' . bin2hex(random_bytes(6));
        mkdir($this->tmpRoot . '/config/local', 0777, true);

        file_put_contents($this->tmpRoot . '/config/app.php', "<?php return ['name' => 'Framework', 'timezone' => 'UTC'];");
        file_put_contents($this->tmpRoot . '/config/log.php', "<?php return ['level' => 'info'];");
        file_put_contents($this->tmpRoot . '/config/local/app.php', "<?php return ['name' => 'Framework Local'];");
        file_put_contents($this->tmpRoot . '/.env', "APP_ENV=local\nAPP_NAME=EnvName\nAPP_DEBUG=true\n");
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->tmpRoot);
        parent::tearDown();
    }

    public function testProvidesConfigInterface(): void
    {
        $provider = new ConfigServiceProvider();

        self::assertTrue($provider->provides(ConfigInterface::class));
        self::assertFalse($provider->provides('config.unknown'));
    }

    public function testRegisterLoadsBaseOverrideAndEnvValues(): void
    {
        $container = new Container();
        $container->add(PathManager::class, new PathManager($this->tmpRoot, [
            PathManager::CONFIG_DIR => 'config',
        ]))->setShared(true);

        $provider = new ConfigServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);

        // env merge has priority over file values due to set() after load.
        self::assertSame('EnvName', $config->getString('app.name'));
        self::assertTrue($config->getBool('app.debug'));
        self::assertSame('local', $config->getString('app.env'));
        self::assertSame('UTC', date_default_timezone_get());
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
