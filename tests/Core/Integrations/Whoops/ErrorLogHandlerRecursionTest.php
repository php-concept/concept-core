<?php declare(strict_types=1);

namespace Tests\Core\Integrations\Whoops;

use Concept\Core\Services\Logger\Contracts\LoggerInterface;
use Concept\Core\Integrations\Whoops\ErrorLogHandler;
use Concept\Core\Integrations\Whoops\PhpErrorLogHandler;
use League\Container\Container;
use League\Container\ServiceProvider\AbstractServiceProvider;
use PHPUnit\Framework\TestCase;
use RuntimeException;
use Whoops\Run as Whoops;

final class ErrorLogHandlerRecursionTest extends TestCase
{
    private string $tempRoot;

    private ?string $previousErrorLog = null;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tempRoot = sys_get_temp_dir() . '/concept-recursion-' . bin2hex(random_bytes(6));
        mkdir($this->tempRoot, 0777, true);
    }

    protected function tearDown(): void
    {
        if ($this->previousErrorLog !== null) {
            ini_set('error_log', $this->previousErrorLog);
        }

        if (isset($GLOBALS['__recursion_whoops']) && $GLOBALS['__recursion_whoops'] instanceof Whoops) {
            $GLOBALS['__recursion_whoops']->unregister();
            unset($GLOBALS['__recursion_whoops']);
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

    public function testWhoopsDoesNotRecurseWhenLoggerCannotBeResolved(): void
    {
        $logFile = $this->tempRoot . '/php-error.log';
        $this->previousErrorLog = ini_get('error_log') ?: '';
        ini_set('error_log', $logFile);

        $container = new Container();
        $container->addServiceProvider(new class extends AbstractServiceProvider {
            public function provides(string $id): bool
            {
                return $id === LoggerInterface::class;
            }

            public function register(): void
            {
                $this->getContainer()->add(LoggerInterface::class, function (): never {
                    throw new RuntimeException('Config provider is missing');
                });
            }
        });

        $whoops = new Whoops();
        $whoops->pushHandler(function (\Throwable $exception) {
            $handler = new PhpErrorLogHandler();
            $handler->setException($exception);

            return $handler->handle();
        });
        $whoops->appendHandler(function (\Throwable $exception) use ($container) {
            $handler = new ErrorLogHandler($container);
            $handler->setException($exception);

            return $handler->handle();
        });
        $whoops->register();

        $GLOBALS['__recursion_whoops'] = $whoops;

        $whoops->handleException(new RuntimeException('original failure'));

        self::assertFileExists($logFile);
        $contents = file_get_contents($logFile);
        self::assertIsString($contents);
        self::assertStringContainsString('app.ERROR: original failure', $contents);
        self::assertStringNotContainsString('Maximum call stack size', $contents);
    }
}
