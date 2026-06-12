<?php declare(strict_types=1);

namespace Tests\Core\Telemetry;

use Concept\Core\Components\Logger\Logger;
use Concept\Core\Telemetry\TelemetryCollector;
use Concept\Core\Telemetry\TelemetryEvent;
use Concept\Core\Telemetry\TelemetryKey;
use Concept\Core\Telemetry\TelemetryLogHandler;
use Monolog\Level;
use Monolog\Logger as Monolog;
use PHPUnit\Framework\TestCase;

final class TelemetryLogHandlerTest extends TestCase
{
    public function testHandlerRecordsLogInTelemetry(): void
    {
        $telemetry = new TelemetryCollector();
        $monolog = new Monolog('test');
        $monolog->pushHandler(new TelemetryLogHandler($telemetry, Level::Debug));

        $logger = new Logger($monolog, null);
        $logger->info('message', ['k' => 'v']);

        $items = array_values($telemetry->toArray(TelemetryEvent::LOG_RECORDED));

        self::assertCount(1, $items);
        self::assertSame('info', $items[0]['context'][TelemetryKey::LEVEL]);
        self::assertSame('message', $items[0]['context'][TelemetryKey::MESSAGE]);
        self::assertSame(['k' => 'v'], $items[0]['context'][TelemetryKey::CONTEXT]);
    }

    public function testHandlerRespectsMinimumLevel(): void
    {
        $telemetry = new TelemetryCollector();
        $monolog = new Monolog('test');
        $monolog->pushHandler(new TelemetryLogHandler($telemetry, Level::Error));

        $logger = new Logger($monolog, null);
        $logger->info('skipped');
        $logger->error('recorded');

        $items = array_values($telemetry->toArray(TelemetryEvent::LOG_RECORDED));

        self::assertCount(1, $items);
        self::assertSame('recorded', $items[0]['context'][TelemetryKey::MESSAGE]);
    }
}
