<?php declare(strict_types=1);

namespace Tests\Core;

use Concept\Core\Components\Logger\DebugLogger;
use Concept\Core\Components\Path\PathManager;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

final class DebugLoggerTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tempRoot = sys_get_temp_dir() . '/concept-core-tests-' . bin2hex(random_bytes(6));
        mkdir($this->tempRoot . '/logs', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->resetStaticInstance();

        $logFile = $this->tempRoot . '/logs/debug.log';
        if (is_file($logFile)) {
            unlink($logFile);
        }

        if (is_dir($this->tempRoot . '/logs')) {
            rmdir($this->tempRoot . '/logs');
        }

        if (is_dir($this->tempRoot)) {
            rmdir($this->tempRoot);
        }

        parent::tearDown();
    }

    public function testLogWritesScalarAndArrayPayloads(): void
    {
        $manager = new PathManager($this->tempRoot, [
            PathManager::LOGS_DIR => 'logs',
        ]);

        DebugLogger::setInstance(new DebugLogger($manager));
        DebugLogger::log('hello', ['id' => 15]);

        $logFile = $this->tempRoot . '/logs/debug.log';
        self::assertFileExists($logFile);

        $content = (string) file_get_contents($logFile);
        self::assertStringContainsString('hello', $content);
        self::assertStringContainsString('"id": 15', $content);
    }

    public function testLogWithoutInstanceIsNoop(): void
    {
        $this->resetStaticInstance();

        DebugLogger::log('should-not-throw');

        self::assertTrue(true);
    }

    private function resetStaticInstance(): void
    {
        $reflection = new ReflectionClass(DebugLogger::class);
        $property = $reflection->getProperty('instance');
        $property->setValue(null, null);
    }
}
