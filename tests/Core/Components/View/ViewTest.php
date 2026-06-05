<?php declare(strict_types=1);

namespace Tests\Core\Components\View;

use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Components\View\TwigView;
use Concept\Core\Events\EventName;
use Concept\Core\Events\Telemetry\ApplicationTelemetryBuffer;
use League\Event\EventDispatcher;
use PHPUnit\Framework\TestCase;
use Twig\Environment as Twig;
use Twig\Extension\ProfilerExtension;
use Twig\Loader\ArrayLoader;
use Twig\Profiler\Profile;

final class ViewTest extends TestCase
{
    public function testRenderAppendsTwigExtensionWhenMissing(): void
    {
        $twig = $this->createMock(Twig::class);
        $twig->expects(self::once())
            ->method('render')
            ->with('home.twig', ['name' => 'Ada'])
            ->willReturn('<h1>Ada</h1>');

        $view = new TwigView($twig);

        self::assertSame('<h1>Ada</h1>', $view->render('home', ['name' => 'Ada']));
    }

    public function testRenderKeepsTwigExtensionWhenProvided(): void
    {
        $twig = $this->createMock(Twig::class);
        $twig->expects(self::once())
            ->method('render')
            ->with('dashboard.twig', ['x' => 1])
            ->willReturn('ok');

        $view = new TwigView($twig);

        self::assertSame('ok', $view->render('dashboard.twig', ['x' => 1]));
    }

    public function testRenderDispatchesTemplateProfileEntriesAfterTwigProfiler(): void
    {
        $buffer = new ApplicationTelemetryBuffer();
        $dispatcher = new EventDispatcher();
        $dispatcher->subscribeTo(
            EventName::VIEW_TEMPLATE_PROFILE_ENTRY,
            static function (object $event) use ($buffer): void {
                $buffer->record($event);
            },
        );

        $profile = new Profile();
        $twig = new Twig(new ArrayLoader(['page.twig' => 'Hello']), ['debug' => true]);
        $twig->addExtension(new ProfilerExtension($profile));

        $view = new TwigView($twig, '.twig', $profile, $dispatcher);
        $view->render('page');

        $records = $buffer->recordsOf(EventName::VIEW_TEMPLATE_PROFILE_ENTRY);
        self::assertNotEmpty($records);

        $spans = $buffer->spans();
        self::assertSame(EventName::VIEW_TEMPLATE_PROFILE_ENTRY, $spans[0]->name);
        self::assertGreaterThan(0.0, $spans[0]->duration);
        self::assertSame('page.twig', $spans[0]->meta['template']);
    }
}
