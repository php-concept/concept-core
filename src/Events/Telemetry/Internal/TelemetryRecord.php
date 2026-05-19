<?php declare(strict_types=1);

namespace Concept\Core\Events\Telemetry\Internal;

use Concept\Core\Events\Contracts\DescribesTelemetryContext;
use Concept\Core\Events\Contracts\TimedEventInterface;
use League\Event\HasEventName;

/**
 * @internal
 */
final readonly class TelemetryRecord
{
    public string $name;
    public float $microtime;
    /** @var array<string, mixed> */
    public array $context;

    public function __construct(
        public object $event
    ) {
        $this->context = $event instanceof DescribesTelemetryContext ? $event->context() : [];
        $this->name = $event instanceof HasEventName ? $event->eventName() : $event::class;

        $endTime = microtime(true);
        if ($event instanceof TimedEventInterface && $event->getEndTime() !== null) {
            $endTime = $event->getEndTime();
        } elseif (is_numeric($this->context['end_time'] ?? null)) {
            $endTime = (float) $this->context['end_time'];
        }

        $this->microtime = $endTime;
    }

    public function toSpan(string $category): Span
    {
        ['start' => $start, 'end' => $end, 'duration' => $duration] = $this->resolveSpanBounds();

        return new Span(
            name: $this->name,
            start: $start,
            end: $end,
            duration: $duration,
            meta: $this->context,
            category: $category,
        );
    }

    /**
     * @return array{start: float, end: float, duration: float}
     */
    private function resolveSpanBounds(): array
    {
        $event = $this->event;
        $context = $this->context;
        $end = $this->microtime;

        if ($event instanceof TimedEventInterface) {
            $start = $event->getStartTime();
            $endTime = $event->getEndTime() ?? $end;
            $duration = $event->getDurationSeconds();

            if ($start !== null) {
                return [
                    'start' => $start,
                    'end' => $endTime,
                    'duration' => $duration ?? max(0.0, $endTime - $start),
                ];
            }

            if ($duration !== null) {
                return [
                    'start' => $endTime - $duration,
                    'end' => $endTime,
                    'duration' => $duration,
                ];
            }
        }

        $startTime = $context['start_time'] ?? null;
        $endTime = $context['end_time'] ?? null;

        if (is_numeric($startTime) && is_numeric($endTime)) {
            $start = (float) $startTime;
            $end = (float) $endTime;

            return [
                'start' => $start,
                'end' => $end,
                'duration' => max(0.0, $end - $start),
            ];
        }

        $duration = $context['duration_seconds'] ?? null;

        if (is_numeric($duration) && (float) $duration > 0.0) {
            $duration = (float) $duration;

            return [
                'start' => $end - $duration,
                'end' => $end,
                'duration' => $duration,
            ];
        }

        return [
            'start' => $end,
            'end' => $end,
            'duration' => 0.0,
        ];
    }
}
