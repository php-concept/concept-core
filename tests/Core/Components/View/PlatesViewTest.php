<?php declare(strict_types=1);

namespace Tests\Core\Components\View;

use Concept\Core\Components\View\PlatesView;
use Concept\Core\Events\EventName;
use Concept\Core\Events\Telemetry\ApplicationTelemetryBuffer;
use League\Event\EventDispatcher;
use League\Plates\Engine;
use PHPUnit\Framework\TestCase;

final class PlatesViewTest extends TestCase
{
    public function testRenderPassesViewNameToEngine(): void
    {
        $engine = $this->createMock(Engine::class);
        $engine->expects(self::once())
            ->method('render')
            ->with('dashboard', ['x' => 1])
            ->willReturn('ok');

        $view = new PlatesView($engine);

        self::assertSame('ok', $view->render('dashboard', ['x' => 1]));
    }

    public function testRenderDispatchesTemplateEvents(): void
    {
        $buffer = new ApplicationTelemetryBuffer();
        $dispatcher = new EventDispatcher();
        $dispatcher->subscribeTo(
            EventName::VIEW_TEMPLATE_RENDERED,
            static function (object $event) use ($buffer): void {
                $buffer->record($event);
            },
        );

        $templateDir = sys_get_temp_dir() . '/plates-view-event-' . bin2hex(random_bytes(6));
        mkdir($templateDir, 0777, true);
        file_put_contents($templateDir . '/hello.php', 'Hello');

        try {
            $engine = new Engine($templateDir);
            $view = new PlatesView($engine, $dispatcher);
            $view->render('hello');

            self::assertNotEmpty($buffer->recordsOf(EventName::VIEW_TEMPLATE_RENDERED));
        } finally {
            unlink($templateDir . '/hello.php');
            rmdir($templateDir);
        }
    }
}
