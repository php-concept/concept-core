<?php declare(strict_types=1);

namespace Tests\Core\Console\Commands;

use Concept\Core\Components\Path\PathManager;
use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Console\Commands\ViewClearCommand;
use Illuminate\Filesystem\Filesystem;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class ViewClearCommandTest extends TestCase
{
    private string $tmpRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpRoot = sys_get_temp_dir() . '/concept-core-test-' . bin2hex(random_bytes(6));
        mkdir($this->tmpRoot, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpRoot)) {
            $this->removeDirectory($this->tmpRoot);
        }
        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }
        rmdir($dir);
    }

    public function testExecuteClearsCacheDirectory(): void
    {
        $cachePath = $this->tmpRoot . '/storage/cache/twig_custom';
        mkdir($cachePath, 0777, true);
        file_put_contents($cachePath . '/template.php', '<?php // cached');

        $pathManager = new PathManager($this->tmpRoot, [
            PathManager::CACHE_DIR => 'storage/cache',
        ]);

        $config = $this->createStub(ConfigInterface::class);
        $config->method('getString')->willReturn('twig_custom');

        $filesystem = new Filesystem();
        $command = new ViewClearCommand($pathManager, $filesystem, $config);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Clearing view cache', $display);
        self::assertStringContainsString('View cache cleared successfully.', $display);
        
        self::assertFileDoesNotExist($cachePath . '/template.php');
        self::assertDirectoryExists($cachePath);
    }

    public function testExecuteWorksIfCacheDirectoryDoesNotExist(): void
    {
        $pathManager = new PathManager($this->tmpRoot, [
            PathManager::CACHE_DIR => 'storage/cache',
        ]);

        $config = $this->createStub(ConfigInterface::class);
        $config->method('getString')->willReturn('views');

        $filesystem = new Filesystem();
        $command = new ViewClearCommand($pathManager, $filesystem, $config);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);
        
        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('View cache cleared successfully.', $tester->getDisplay());
    }

    public function testExecuteReturnsFailureOnException(): void
    {
        $pathManager = $this->createStub(PathManager::class);
        $pathManager->method('get')->willThrowException(new \RuntimeException('path error'));

        $config = $this->createStub(ConfigInterface::class);

        $filesystem = new Filesystem();
        $command = new ViewClearCommand($pathManager, $filesystem, $config);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Failed to clear view cache: path error', $tester->getDisplay());
    }
}
