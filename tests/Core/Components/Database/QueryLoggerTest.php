<?php declare(strict_types=1);

namespace Tests\Core\Components\Database;

use Concept\Core\Components\Database\QueryLogger;
use Concept\Core\Foundation\PathManager;
use Concept\Core\Foundation\PathName;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Database\Capsule\Manager as CapsuleManager;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Events\Dispatcher;
use Monolog\Handler\StreamHandler;
use Monolog\Level;
use Monolog\Logger as Monolog;
use PHPUnit\Framework\TestCase;

final class QueryLoggerTest extends TestCase
{
    private string $tmpRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpRoot = sys_get_temp_dir() . '/query-logger-' . bin2hex(random_bytes(6));
        mkdir($this->tmpRoot . '/storage/logs', 0777, true);
    }

    protected function tearDown(): void
    {
        $logFile = $this->tmpRoot . '/storage/logs/query.log';
        if (is_file($logFile)) {
            unlink($logFile);
        }
        if (is_dir($this->tmpRoot . '/storage/logs')) {
            rmdir($this->tmpRoot . '/storage/logs');
        }
        if (is_dir($this->tmpRoot . '/storage')) {
            rmdir($this->tmpRoot . '/storage');
        }
        if (is_dir($this->tmpRoot)) {
            rmdir($this->tmpRoot);
        }
        parent::tearDown();
    }

    public function testWritesSqlToQueryLogFile(): void
    {
        $pathManager = new PathManager($this->tmpRoot, [PathName::LOGS => 'storage/logs']);
        $logFile = $pathManager->get(PathName::LOGS, 'query.log');
        $monolog = new Monolog('query');
        $monolog->pushHandler(new StreamHandler($logFile, Level::Debug));

        $capsule = new CapsuleManager();
        $capsule->addConnection(['driver' => 'sqlite', 'database' => ':memory:']);
        $capsule->bootEloquent();
        $capsule->setEventDispatcher(new Dispatcher(new IlluminateContainer()));

        $logger = new QueryLogger($monolog, null);
        $capsule->getConnection()->listen(function (QueryExecuted $query) use ($logger): void {
            $logger->log($query);
        });
        $capsule->getConnection()->select('select 1');
        $monolog->close();

        self::assertFileExists($logFile);
        self::assertStringContainsString('SQL: select 1', (string) file_get_contents($logFile));
    }
}
