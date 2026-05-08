<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Logger\DebugLogger;
use Concept\Core\Components\Path\PathManager;
use Concept\Core\Providers\DebugLoggerServiceProvider;
use League\Container\Container;
use PHPUnit\Framework\TestCase;

final class DebugLoggerServiceProviderTest extends TestCase
{
    public function testProvidesDebugLoggerClass(): void
    {
        $provider = new DebugLoggerServiceProvider();

        self::assertTrue($provider->provides(DebugLogger::class));
        self::assertFalse($provider->provides('foo.bar'));
    }

    public function testRegisterBindsSharedDebugLoggerAndSetsStaticInstance(): void
    {
        $tmpRoot = sys_get_temp_dir() . '/provider-debug-' . bin2hex(random_bytes(6));
        mkdir($tmpRoot . '/storage/logs', 0777, true);

        try {
            $container = new Container();
            $container->add(PathManager::class, new PathManager($tmpRoot, [
                PathManager::LOGS_DIR => 'storage/logs',
            ]))->setShared(true);

            $provider = new DebugLoggerServiceProvider();
            $provider->setContainer($container);
            $provider->register();
            $provider->boot();

            $loggerA = $container->get(DebugLogger::class);
            $loggerB = $container->get(DebugLogger::class);
            self::assertSame($loggerA, $loggerB);

            DebugLogger::log('provider-wired');

            $content = (string) file_get_contents($tmpRoot . '/storage/logs/debug.log');
            self::assertStringContainsString('provider-wired', $content);
        } finally {
            $this->removeTree($tmpRoot);
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
