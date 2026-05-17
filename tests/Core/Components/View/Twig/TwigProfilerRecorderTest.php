<?php declare(strict_types=1);

namespace Tests\Core\Components\View\Twig;

use Concept\Core\Components\View\Twig\TwigProfilerRecorder;
use Concept\Core\Events\EventName;
use Concept\Core\Events\Telemetry\ApplicationTelemetryBuffer;
use League\Event\EventDispatcher;
use PHPUnit\Framework\TestCase;
use Twig\Profiler\Profile;

final class TwigProfilerRecorderTest extends TestCase
{
    public function testDispatchProfileRecordsEventWithTimestamps(): void
    {
        $buffer = new ApplicationTelemetryBuffer();
        $dispatcher = new EventDispatcher();
        $dispatcher->subscribeTo(
            EventName::VIEW_TEMPLATE_PROFILE_ENTRY,
            static function (object $event) use ($buffer): void {
                $buffer->record($event);
            },
        );

        $template = new Profile('layout.twig', Profile::TEMPLATE, 'layout.twig');
        $template->leave();

        TwigProfilerRecorder::dispatchProfile($dispatcher, $template, 0);

        $records = $buffer->recordsOf(EventName::VIEW_TEMPLATE_PROFILE_ENTRY);
        self::assertCount(1, $records);

        $spans = $buffer->spans();
        self::assertSame(EventName::VIEW_TEMPLATE_PROFILE_ENTRY, $spans[0]['name']);
        self::assertGreaterThan(0.0, $spans[0]['duration']);
        self::assertSame('layout.twig', $spans[0]['meta']['template']);
    }
}
