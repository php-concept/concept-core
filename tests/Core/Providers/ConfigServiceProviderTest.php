<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\PathManager;
use Concept\Core\Foundation\PathName;
use Concept\Core\Providers\ConfigServiceProvider;
use League\Container\Container;
use PHPUnit\Framework\TestCase;

final class ConfigServiceProviderTest extends TestCase
{
    private string $tmpRoot;
    /** @var array<string, string|false|null> */
    private array $previousEnv = [];

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpRoot = sys_get_temp_dir() . '/provider-config-' . bin2hex(random_bytes(6));
        mkdir($this->tmpRoot . '/config/local', 0777, true);

        file_put_contents($this->tmpRoot . '/config/app.php', "<?php return ['name' => 'Framework', 'timezone' => 'UTC'];");
        file_put_contents($this->tmpRoot . '/config/log.php', "<?php return ['level' => 'info'];");
        file_put_contents($this->tmpRoot . '/config/local/app.php', "<?php return ['name' => 'Framework Local'];");
        file_put_contents($this->tmpRoot . '/.env', "APP_ENV=local\nAPP_NAME=EnvName\nAPP_DEBUG=true\n");

        foreach (['APP_ENV', 'APP_NAME', 'APP_DEBUG'] as $key) {
            $this->previousEnv[$key] = getenv($key);
            putenv($key);
            unset($_ENV[$key], $_SERVER[$key]);
        }
    }

    protected function tearDown(): void
    {
        foreach ($this->previousEnv as $key => $value) {
            if ($value === false || $value === null) {
                putenv($key);
                unset($_ENV[$key], $_SERVER[$key]);
                continue;
            }

            putenv(sprintf('%s=%s', $key, $value));
            $_ENV[$key] = $value;
            $_SERVER[$key] = $value;
        }

        $this->removeTree($this->tmpRoot);
        parent::tearDown();
    }

    public function testProvidesConfigInterface(): void
    {
        $provider = new ConfigServiceProvider();

        self::assertTrue($provider->provides(ConfigInterface::class));
        self::assertFalse($provider->provides('config.unknown'));
    }

    public function testBootLoadsBaseOverrideAndEnvValues(): void
    {
        $container = new Container();
        $container->add(PathManager::class, new PathManager($this->tmpRoot, [
            PathName::CONFIG => 'config',
        ]))->setShared(true);

        $provider = new ConfigServiceProvider();
        $provider->setContainer($container);
        $provider->boot();

        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);

        // env merge has priority over file values due to set() after load.
        self::assertSame('EnvName', $config->getString('app.name'));
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
