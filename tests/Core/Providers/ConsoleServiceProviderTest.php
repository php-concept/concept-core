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
