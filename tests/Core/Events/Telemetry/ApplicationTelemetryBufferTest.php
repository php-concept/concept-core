<?php declare(strict_types=1);

namespace Tests\Core\Events\Telemetry;

use Concept\Core\Components\Component\Contracts\ComponentInterface;
use Concept\Core\Events\EventName;
use Concept\Core\Events\Framework\ComponentRegistering;
use Concept\Core\Events\Framework\ServiceAwakening;
use Concept\Core\Events\Http\RouteCallableInvoked;
use Concept\Core\Events\Http\RouterDispatchStarted;
use Concept\Core\Events\Telemetry\ApplicationTelemetryBuffer;
use Concept\Core\Events\View\TemplateRendered;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class ApplicationTelemetryBufferTest extends TestCase
{
    public function testStatisticsBuildsTimelineAndCounts(): void
    {
        $buffer = new ApplicationTelemetryBuffer();
        $request = $this->createStub(ServerRequestInterface::class);

        $buffer->record(new RouterDispatchStarted($request));
        $buffer->record(new RouteCallableInvoked(['GET'], '/users/{id}', 'UserController::show', ['id' => '1'], 0.012));
        $buffer->record(new TemplateRendered('users/show.twig', 0.004));

        $stats = $buffer->statistics();

        self::assertSame(3, $stats['total']);
        self::assertNotNull($stats['started_at']);
        self::assertNotNull($stats['ended_at']);
        self::assertGreaterThanOrEqual(0.0, $stats['wall_time_seconds']);
        self::assertSame(1, $stats['counts_by_name'][EventName::HTTP_ROUTER_DISPATCH_STARTED]);
        self::assertSame(1, $stats['counts_by_name'][EventName::HTTP_ROUTE_CALLABLE_INVOKED]);
        self::assertSame(1, $stats['counts_by_name'][EventName::VIEW_TEMPLATE_RENDERED]);
        self::assertCount(2, $stats['timeline']); // Only Invoked and Rendered (Started has 0 duration and is not TimedEvent)
        self::assertSame(0.012, $stats['timeline'][0]['duration_seconds']);
        self::assertSame('users/show.twig', $stats['timeline'][1]['context']['template']);
    }

    public function testStatisticsIncludesDictionary(): void
    {
        $buffer = new ApplicationTelemetryBuffer();
        $component = $this->createStub(ComponentInterface::class);
        $buffer->record(new ComponentRegistering($component));
        $buffer->record(new ServiceAwakening('Cache'));

        $stats = $buffer->statistics();

        self::assertSame(2, $stats['total']);
        self::assertCount(0, $stats['timeline']);
        self::assertArrayHasKey('components', $stats['dictionary']);
        self::assertArrayHasKey('services', $stats['dictionary']);
        self::assertContains(get_class($component), $stats['dictionary']['components']);
        self::assertContains('Cache', $stats['dictionary']['services']);
    }

    public function testStatisticsReturnsEmptyStructureWhenNoRecords(): void
    {
        $stats = (new ApplicationTelemetryBuffer())->statistics();

        self::assertSame(0, $stats['total']);
        self::assertNull($stats['started_at']);
        self::assertSame([], $stats['timeline']);
    }

    public function testSpansReturnsEmptyListWhenNoRecords(): void
    {
        self::assertSame([], (new ApplicationTelemetryBuffer())->spans());
    }

    public function testSpansUsesExplicitStartAndEndTimesFromContext(): void
    {
        $buffer = new ApplicationTelemetryBuffer();
        $buffer->record(new class implements \Concept\Core\Events\Contracts\DescribesTelemetryContext, \League\Event\HasEventName {
            public function eventName(): string
            {
                return 'view.template_profile_entry';
            }

            public function context(): array
            {
                return [
                    'template' => 'partial.twig',
                    'type' => 'template',
                    'name' => 'partial.twig',
                    'start_time' => 1000.0,
                    'end_time' => 1000.05,
                    'duration_seconds' => 0.05,
                ];
            }
        });

        $spans = $buffer->spans();
        self::assertCount(1, $spans);
        self::assertSame('view.template_profile_entry', $spans[0]->name);
        self::assertSame(1000.0, $spans[0]->start);
        self::assertSame(1000.05, $spans[0]->end);
        self::assertEqualsWithDelta(0.05, $spans[0]->duration, 0.0001);
    }

    public function testRecordsOfFiltersByEventName(): void
    {
        $buffer = new ApplicationTelemetryBuffer();
        $buffer->record(new RouterDispatchStarted($this->createStub(ServerRequestInterface::class)));
        $buffer->record(new class implements \Concept\Core\Events\Contracts\DescribesTelemetryContext, \League\Event\HasEventName {
            public function eventName(): string
            {
                return EventName::VIEW_TEMPLATE_PROFILE_ENTRY;
            }

            public function context(): array
            {
                return ['template' => 'a.twig', 'type' => 'template', 'name' => 'a.twig'];
            }
        });

        self::assertCount(1, $buffer->recordsOf(EventName::VIEW_TEMPLATE_PROFILE_ENTRY));
    }

    public function testSpansBuildsIntervalsAndCategories(): void
    {
        $buffer = new ApplicationTelemetryBuffer();
        $request = $this->createStub(ServerRequestInterface::class);

        $buffer->record(new RouterDispatchStarted($request));
        $buffer->record(new RouteCallableInvoked(['GET'], '/users/{id}', 'UserController::show', ['id' => '1'], 0.012));
        $buffer->record(new TemplateRendered('users/show.twig', 0.004));

        $spans = $buffer->spans();

        self::assertCount(2, $spans); // Skip RouterDispatchStarted (0 duration, not TimedEvent)

        self::assertSame(EventName::HTTP_ROUTE_CALLABLE_INVOKED, $spans[0]->name);
        self::assertSame('http', $spans[0]->category);
        self::assertSame(0.012, $spans[0]->duration);
        self::assertSame($spans[0]->end - 0.012, $spans[0]->start);

        self::assertSame(EventName::VIEW_TEMPLATE_RENDERED, $spans[1]->name);
        self::assertSame('view', $spans[1]->category);
        self::assertSame(0.004, $spans[1]->duration);
        self::assertSame('users/show.twig', $spans[1]->meta['template']);
    }
}
