<?php declare(strict_types=1);

namespace Tests\Core\Components\View;

use Concept\Core\Components\Telemetry\TelemetryCollector;
use Concept\Core\Components\Telemetry\TelemetryEvent;
use Concept\Core\Components\View\PlatesView;
use Concept\Core\Components\View\TwigView;
use League\Plates\Engine;
use PHPUnit\Framework\TestCase;
use Twig\Environment as Twig;

final class ViewTest extends TestCase
{
    public function testRenderAppendsTwigExtensionWhenMissing(): void
    {
        $twig = $this->createMock(Twig::class);
        $twig->expects(self::once())
            ->method('render')
            ->with('home.twig', ['name' => 'Ada'])
            ->willReturn('<h1>Ada</h1>');

        $view = new TwigView($twig, '.twig', null);

        self::assertSame('<h1>Ada</h1>', $view->render('home', ['name' => 'Ada']));
    }

    public function testRenderKeepsTwigExtensionWhenProvided(): void
    {
        $twig = $this->createMock(Twig::class);
        $twig->expects(self::once())
            ->method('render')
            ->with('dashboard.twig', ['x' => 1])
            ->willReturn('ok');

        $view = new TwigView($twig, '.twig', null);

        self::assertSame('ok', $view->render('dashboard.twig', ['x' => 1]));
    }

    public function testTwigViewRecordsTemplateTelemetry(): void
    {
        $twig = $this->createStub(Twig::class);
        $twig->method('render')->willReturn('ok');

        $telemetry = new TelemetryCollector();
        $view = new TwigView($twig, '.twig', $telemetry);
        $view->render('page');

        $items = array_values($telemetry->toArray(TelemetryEvent::TPL_RENDERED));

        self::assertCount(1, $items);
        self::assertSame(TelemetryEvent::TPL_RENDERED, $items[0]['name']);
        self::assertSame(['view' => 'page.twig'], $items[0]['context']);
        self::assertNotNull($items[0]['duration']);
    }

    public function testPlatesViewRecordsTemplateTelemetry(): void
    {
        $engine = $this->createStub(Engine::class);
        $engine->method('render')->willReturn('ok');

        $telemetry = new TelemetryCollector();
        $view = new PlatesView($engine, $telemetry);
        $view->render('page');

        $items = array_values($telemetry->toArray(TelemetryEvent::TPL_RENDERED));

        self::assertCount(1, $items);
        self::assertSame(TelemetryEvent::TPL_RENDERED, $items[0]['name']);
        self::assertSame(['view' => 'page'], $items[0]['context']);
        self::assertNotNull($items[0]['duration']);
    }
}
