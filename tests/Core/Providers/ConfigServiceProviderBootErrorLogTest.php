<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Path\PathManager;
use Concept\Core\Integrations\Whoops\PhpErrorLogHandler;
use Concept\Core\Providers\ConfigServiceProvider;
use League\Container\Container;
use PHPUnit\Framework\TestCase;
use Throwable;

final class ConfigServiceProviderBootErrorLogTest extends TestCase
{
    private string $tmpRoot;

    private ?string $previousErrorLog = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpRoot = sys_get_temp_dir() . '/provider-config-err-' . bin2hex(random_bytes(6));
        mkdir($this->tmpRoot . '/config/local', 0777, true);
    }

    protected function tearDown(): void
    {
        if ($this->previousErrorLog !== null) {
            ini_set('error_log', $this->previousErrorLog);
        }

        $this->removeTree($this->tmpRoot);
        parent::tearDown();
    }

    public function testBootLogsToErrorLogWhenBaseConfigHasInvalidPhp(): void
    {
        $logFile = $this->tmpRoot . '/php-error.log';
        $this->previousErrorLog = ini_get('error_log') ?: '';
        ini_set('error_log', $logFile);

        // broken PHP syntax (Noodlehaus\Config loads PHP config files eagerly)
        file_put_contents($this->tmpRoot . '/config/app.php', "<?php return [\n");
        file_put_contents($this->tmpRoot . '/.env', "APP_ENV=local\nAPP_DEBUG=false\n");

        $container = new Container();
        $container->add(
            PathManager::class,
            new PathManager($this->tmpRoot, [
                PathManager::CONFIG_DIR => 'config',
            ])
        )->setShared(true);

        $provider = new ConfigServiceProvider();
        $provider->setContainer($container);

        try {
            $provider->boot();
            self::fail('Expected exception from invalid base config PHP.');
        } catch (Throwable $e) {
            $handler = new PhpErrorLogHandler();
            $handler->setException($e);
            $handler->handle();

            self::assertFileExists($logFile);
            $contents = file_get_contents($logFile);
            self::assertIsString($contents);

            self::assertStringContainsString('app.ERROR:', $contents);
            self::assertStringContainsString('"bootstrap":true', $contents);
        }
    }

    public function testBootLogsToErrorLogWhenOverrideConfigHasInvalidPhp(): void
    {
        $logFile = $this->tmpRoot . '/php-error.log';
        $this->previousErrorLog = ini_get('error_log') ?: '';
        ini_set('error_log', $logFile);

        file_put_contents($this->tmpRoot . '/config/app.php', "<?php return ['timezone' => 'UTC'];\n");
        file_put_contents($this->tmpRoot . '/config/local/app.php', "<?php return ];\n");
        file_put_contents($this->tmpRoot . '/.env', "APP_ENV=local\nAPP_DEBUG=false\n");

        $container = new Container();
        $container->add(
            PathManager::class,
            new PathManager($this->tmpRoot, [
                PathManager::CONFIG_DIR => 'config',
            ])
        )->setShared(true);

        $provider = new ConfigServiceProvider();
        $provider->setContainer($container);

        try {
            $provider->boot();
            self::fail('Expected exception from invalid override config PHP.');
        } catch (Throwable $e) {
            $handler = new PhpErrorLogHandler();
            $handler->setException($e);
            $handler->handle();

            self::assertFileExists($logFile);
            $contents = file_get_contents($logFile);
            self::assertIsString($contents);

            self::assertStringContainsString('app.ERROR:', $contents);
            self::assertStringContainsString('"bootstrap":true', $contents);
        }
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

