<?php declare(strict_types=1);

namespace Tests\Core\Components\View\Twig;

use Concept\Core\Components\View\Twig\TelemetryProfilerExtension;
use Concept\Core\Events\EventName;
use Concept\Core\Events\Telemetry\ApplicationTelemetryBuffer;
use League\Event\EventDispatcher;
use PHPUnit\Framework\TestCase;
use Twig\Profiler\Profile;

final class TelemetryProfilerExtensionTest extends TestCase
{
    public function testLeaveDispatchesTelemetryForEachFinishedProfile(): void
    {
        $buffer = new ApplicationTelemetryBuffer();
        $dispatcher = new EventDispatcher();
        $dispatcher->subscribeTo(
            EventName::VIEW_TEMPLATE_PROFILE_ENTRY,
            static function (object $event) use ($buffer): void {
                $buffer->record($event);
            },
        );

        $root = new Profile();
        $extension = new TelemetryProfilerExtension($root, $dispatcher);

        $child = new Profile('partial.twig', Profile::TEMPLATE, 'partial.twig');
        $extension->enter($child);
        $extension->leave($child);

        self::assertCount(1, $buffer->recordsOf(EventName::VIEW_TEMPLATE_PROFILE_ENTRY));
    }
}
