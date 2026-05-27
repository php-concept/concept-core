<?php declare(strict_types=1);

namespace Tests\Core\Integrations\Whoops;

use Concept\Core\Integrations\Whoops\EarlyBootstrapErrorLogHandler;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class EarlyBootstrapErrorLogHandlerTest extends TestCase
{
    private string $tempRoot;

    private ?string $previousErrorLog = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempRoot = sys_get_temp_dir() . '/concept-early-log-' . bin2hex(random_bytes(6));
        mkdir($this->tempRoot, 0777, true);
    }

    protected function tearDown(): void
    {
        if ($this->previousErrorLog !== null) {
            ini_set('error_log', $this->previousErrorLog);
        }

        $logFile = $this->tempRoot . '/php-error.log';
        if (is_file($logFile)) {
            unlink($logFile);
        }
        if (is_dir($this->tempRoot)) {
            rmdir($this->tempRoot);
        }

        parent::tearDown();
    }

    public function testWritesExceptionToErrorLog(): void
    {
        $logFile = $this->tempRoot . '/php-error.log';
        $this->previousErrorLog = ini_get('error_log') ?: '';
        ini_set('error_log', $logFile);

        $handler = new EarlyBootstrapErrorLogHandler();
        $handler->setException(new RuntimeException('bootstrap failed'));
        $handler->handle();

        self::assertFileExists($logFile);

        $contents = file_get_contents($logFile);
        self::assertIsString($contents);
        self::assertStringContainsString('app.ERROR: bootstrap failed', $contents);
        self::assertStringContainsString('"bootstrap":true', $contents);
    }
}
