<?php declare(strict_types=1);

namespace Tests\Core\Events\Telemetry;

use Concept\Core\Events\EventName;
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
        self::assertCount(3, $stats['timeline']);
        self::assertSame(0.012, $stats['timeline'][1]['duration_seconds']);
        self::assertSame('users/show.twig', $stats['timeline'][2]['context']['template']);
    }

    public function testStatisticsReturnsEmptyStructureWhenNoRecords(): void
    {
        $stats = (new ApplicationTelemetryBuffer())->statistics();

        self::assertSame(0, $stats['total']);
        self::assertNull($stats['started_at']);
        self::assertSame([], $stats['timeline']);
    }
}
