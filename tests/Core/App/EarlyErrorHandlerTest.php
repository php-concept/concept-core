<?php declare(strict_types=1);

namespace Tests\Core\App;

use Concept\Core\App;
use PHPUnit\Framework\Attributes\PreserveGlobalState;
use PHPUnit\Framework\Attributes\RunInSeparateProcess;
use PHPUnit\Framework\TestCase;
use Whoops\Handler\PlainTextHandler;
use Whoops\Run as Whoops;

final class EarlyErrorHandlerTest extends TestCase
{
    private string $tempRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempRoot = sys_get_temp_dir() . '/concept-early-err-' . bin2hex(random_bytes(6));
        mkdir($this->tempRoot, 0777, true);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->tempRoot . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->tempRoot)) {
            rmdir($this->tempRoot);
        }
        parent::tearDown();
    }

    /**
     * Verifies that the early error handler registers a PlainTextHandler when running in CLI.
     */
    #[RunInSeparateProcess]
    #[PreserveGlobalState(false)]
    public function testEarlyErrorHandlerRegistersPlainTextHandlerInCli(): void
    {
        // In our test environment PHP_SAPI is 'cli'
        $app = App::create($this->tempRoot, []);
        
        /** @var Whoops $whoops */
        $whoops = $app->getContainer()->get(Whoops::class);
        $handlers = $whoops->getHandlers();

        self::assertNotEmpty($handlers, 'Early error handler should register at least one handler');
        self::assertInstanceOf(PlainTextHandler::class, $handlers[0], 'In CLI mode, PlainTextHandler should be registered');
    }
}
