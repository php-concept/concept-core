<?php declare(strict_types=1);

namespace Tests\Core\Integrations\Whoops;

use Concept\Core\Components\Path\PathManager;
use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Http\Protocol\HttpStatusCode;
use Concept\Core\Integrations\Whoops\ProductionErrorHandler;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Core\ArrayContainer;

final class ProductionErrorHandlerTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/whoops-handler-' . bin2hex(random_bytes(6));
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpDir)) {
            $this->removeDirectory($this->tmpDir);
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

    public function testPrepareResponseCodeUsesHttpExceptionStatusCode(): void
    {
        $handler = new ProductionErrorHandler(new ArrayContainer([]), $this->tmpDir);

        $exception = new class ('boom', 0) extends \RuntimeException {
            public function getStatusCode(): int
            {
                return 422;
            }
        };

        $code = $this->invokePrivate($handler, 'prepareResponseCode', [$exception]);

        self::assertSame(422, $code);
    }

    public function testPrepareResponseCodeFallsBackTo500ForOutOfRangeCodes(): void
    {
        $handler = new ProductionErrorHandler(new ArrayContainer([]), $this->tmpDir);

        $code = $this->invokePrivate($handler, 'prepareResponseCode', [new \RuntimeException('x', 200)]);

        self::assertSame(HttpStatusCode::INTERNAL_SERVER_ERROR, $code);
    }

    public function testRenderFallbackUsesSpecificTemplateWhenExists(): void
    {
        file_put_contents($this->tmpDir . '/404.php', '<?php echo "specific:" . $code . ":" . $exception->getMessage();');

        $handler = new ProductionErrorHandler(new ArrayContainer([]), $this->tmpDir);
        $handler->setException(new \RuntimeException('not found'));

        $output = $this->captureOutput(fn() => $this->invokePrivate($handler, 'renderFallback', [$this->tmpDir, 404]));

        self::assertSame('specific:404:not found', $output);
    }

    public function testRenderFallbackFallsBackTo500TemplateWhenSpecificMissing(): void
    {
        file_put_contents($this->tmpDir . '/500.php', '<?php echo "fallback:" . $code;');

        $handler = new ProductionErrorHandler(new ArrayContainer([]), $this->tmpDir);
        $handler->setException(new \RuntimeException('oops'));

        $output = $this->captureOutput(fn() => $this->invokePrivate($handler, 'renderFallback', [$this->tmpDir, 403]));

        self::assertSame('fallback:403', $output);
    }

    public function testRenderFallbackPrintsCriticalHtmlWhenNoTemplateFilesExist(): void
    {
        $handler = new ProductionErrorHandler(new ArrayContainer([]), $this->tmpDir);
        $handler->setException(new \RuntimeException('oops'));

        $output = $this->captureOutput(fn() => $this->invokePrivate($handler, 'renderFallback', [$this->tmpDir, 403]));

        self::assertStringContainsString('<h1>403', $output);
        self::assertStringContainsString('Something went wrong and the error page could not be loaded.', $output);
    }

    public function testRenderErrorPageRendersTemplateForKnownStatusCode(): void
    {
        $viewsDir = $this->tmpDir . '/views';
        mkdir($viewsDir . '/errors', 0777, true);
        file_put_contents($viewsDir . '/errors/404' . ViewInterface::DEFAULT_EXTENSION, 'noop');

        $pathManager = new PathManager($this->tmpDir, [PathManager::VIEWS_DIR => 'views']);

        $renderedWith = null;
        $view = $this->createStub(ViewInterface::class);
        $view->method('render')->willReturnCallback(function (string $name, array $data) use (&$renderedWith): string {
            $renderedWith = [$name, $data];

            return '<h1>404 page</h1>';
        });

        $container = new ArrayContainer([
            PathManager::class => $pathManager,
            ViewInterface::class => $view,
        ]);

        $handler = new ProductionErrorHandler($container, $this->tmpDir);
        $exception = new \RuntimeException('not found');

        $output = $this->invokePrivate($handler, 'renderErrorPage', [$exception, 404]);

        self::assertSame('<h1>404 page</h1>', $output);
        self::assertSame('errors/404', $renderedWith[0]);
        self::assertSame($exception, $renderedWith[1]['exception']);
    }

    public function testRenderErrorPageFallsBackTo500TemplateWhenSpecificMissing(): void
    {
        $pathManager = new PathManager($this->tmpDir, [PathManager::VIEWS_DIR => 'views']);

        $renderedTemplate = null;
        $view = $this->createStub(ViewInterface::class);
        $view->method('render')->willReturnCallback(function (string $name) use (&$renderedTemplate): string {
            $renderedTemplate = $name;

            return '<h1>generic</h1>';
        });

        $container = new ArrayContainer([
            PathManager::class => $pathManager,
            ViewInterface::class => $view,
        ]);

        $handler = new ProductionErrorHandler($container, $this->tmpDir);

        $this->invokePrivate($handler, 'renderErrorPage', [new \RuntimeException('boom'), 418]);

        self::assertSame('errors/500', $renderedTemplate);
    }

    private function invokePrivate(object $object, string $method, array $args): mixed
    {
        $ref = new \ReflectionClass($object);
        $m = $ref->getMethod($method);
        $m->setAccessible(true);

        return $m->invokeArgs($object, $args);
    }

    private function captureOutput(callable $callback): string
    {
        ob_start();
        try {
            $callback();

            return (string) ob_get_contents();
        } finally {
            ob_end_clean();
        }
    }
}
