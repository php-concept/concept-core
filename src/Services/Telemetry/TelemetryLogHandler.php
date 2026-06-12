<?php declare(strict_types=1);

namespace Concept\Core\Services\Telemetry;

use Monolog\Handler\Handler;
use Monolog\Level;
use Monolog\LogRecord;
use Monolog\Logger;

final class TelemetryLogHandler extends Handler
{
    private Level $level;

    public function __construct(
        private readonly TelemetryCollector $collector,
        Level $level = Level::Debug,
    ) {
        $this->level = Logger::toMonologLevel ($level);
    }

    public function isHandling(LogRecord $record): bool
    {
        return $record->level->value >= $this->level->value;
    }

    public function handle(LogRecord $record): bool
    {
        if ($record->level->value < $this->level->value) {
            return false;
        }

        $this->collector->record(
            TelemetryEvent::LOG_RECORDED,
            [
                TelemetryKey::LEVEL => strtolower($record->level->getName()),
                TelemetryKey::MESSAGE => $record->message,
                TelemetryKey::CONTEXT => $record->context,
            ]
        );

        return false;
    }
}
