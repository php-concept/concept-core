<?php declare(strict_types=1);

namespace Tests\Core\Telemetry;

use Concept\Core\Services\View\Contracts\ViewInterface;
use Concept\Core\Telemetry\TelemetryCollector;
use Concept\Core\Telemetry\TelemetryEvent;
use Concept\Core\Telemetry\TelemetryKey;
use PHPUnit\Framework\TestCase;

final class TelemetryCollectorTest extends TestCase
{
    public function testStartAndFinishRecordsDuration(): void
    {
        $collector = new TelemetryCollector();
        $id = $collector->start(TelemetryEvent::TPL_RENDERED, [TelemetryKey::VIEW => 'home.twig']);
        $collector->finish(TelemetryEvent::TPL_RENDERED, $id);

        $items = array_values($collector->toArray(TelemetryEvent::TPL_RENDERED));

        self::assertCount(1, $items);
        self::assertSame(TelemetryEvent::TPL_RENDERED, $items[0]['name']);
        self::assertSame([TelemetryKey::VIEW => 'home.twig'], $items[0]['context']);
        self::assertNotNull($items[0]['started_at']);
        self::assertNotNull($items[0]['finished_at']);
        self::assertNotNull($items[0]['duration']);
    }

    public function testMarkCreatesFinishedItem(): void
    {
        $collector = new TelemetryCollector();
        $collector->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, ViewInterface::class);

        $items = array_values($collector->toArray(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING));

        self::assertCount(1, $items);
        self::assertSame(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, $items[0]['name']);
        self::assertSame([TelemetryKey::NAME => ViewInterface::class], $items[0]['context']);
        self::assertNotNull($items[0]['duration']);
    }

    public function testToArrayReturnsGroupedItems(): void
    {
        $collector = new TelemetryCollector();
        $collector->mark(TelemetryEvent::TPL_RENDERED, 'page');
        $collector->mark(TelemetryEvent::DB_QUERY_EXECUTED, 'select 1');

        $items = $collector->toArray();

        self::assertArrayHasKey(TelemetryEvent::TPL_RENDERED, $items);
        self::assertArrayHasKey(TelemetryEvent::DB_QUERY_EXECUTED, $items);
        self::assertCount(1, array_values($items[TelemetryEvent::TPL_RENDERED]));
        self::assertCount(1, array_values($items[TelemetryEvent::DB_QUERY_EXECUTED]));
    }
}
