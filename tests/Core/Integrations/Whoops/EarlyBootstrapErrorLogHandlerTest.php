<?php declare(strict_types=1);

namespace Tests\Core\Integrations\Whoops;

use Concept\Core\Integrations\Whoops\EarlyBootstrapErrorLogHandler;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Whoops\Run as Whoops;

final class EarlyBootstrapErrorLogHandlerTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempRoot = sys_get_temp_dir() . '/concept-early-log-' . bin2hex(random_bytes(6));
        mkdir($this->tempRoot . '/storage/logs', 0777, true);
    }

    protected function tearDown(): void
    {
        $logDir = $this->tempRoot . '/storage/logs';
        foreach (glob($logDir . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($logDir)) {
            rmdir($logDir);
        }
        if (is_dir($this->tempRoot . '/storage')) {
            rmdir($this->tempRoot . '/storage');
        }
        if (is_dir($this->tempRoot)) {
            rmdir($this->tempRoot);
        }
        parent::tearDown();
    }

    public function testWritesExceptionToAppLogFile(): void
    {
        $whoops = new Whoops();
        $whoops->pushHandler(new EarlyBootstrapErrorLogHandler($this->tempRoot));
        $whoops->register();

        try {
            throw new RuntimeException('bootstrap failed');
        } catch (RuntimeException $exception) {
            $handler = new EarlyBootstrapErrorLogHandler($this->tempRoot);
            $handler->setException($exception);
            $handler->handle();
        }

        $logFiles = glob($this->tempRoot . '/storage/logs/app-*.log');
        self::assertNotFalse($logFiles);
        self::assertCount(1, $logFiles);

        $contents = file_get_contents($logFiles[0]);
        self::assertIsString($contents);
        self::assertStringContainsString('app.ERROR: bootstrap failed', $contents);
        self::assertStringContainsString('"bootstrap":true', $contents);
    }
}
