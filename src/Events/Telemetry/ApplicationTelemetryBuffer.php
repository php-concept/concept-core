<?php declare(strict_types=1);

namespace Concept\Core\Events\Telemetry;

use Concept\Core\Events\Contracts\DescribesTelemetryContext;
use Concept\Core\Events\Contracts\TimedEventInterface;
use League\Event\HasEventName;

/**
 * Collects structured events for in-request inspection (e.g. DebugBar tab, log enrichment).
 */
final class ApplicationTelemetryBuffer
{
    /**
     * @var list<array{name: string, microtime: float, context: array<string, mixed>}>
     */
    private array $records = [];

    public function record(object $event): void
    {
        $context = $event instanceof DescribesTelemetryContext ? $event->context() : [];
        $name = $event instanceof HasEventName ? $event->eventName() : $event::class;

        $endTime = microtime(true);
        if ($event instanceof TimedEventInterface && $event->getEndTime() !== null) {
            $endTime = $event->getEndTime();
        } elseif (is_numeric($context['end_time'] ?? null)) {
            $endTime = (float) $context['end_time'];
        }

        $this->records[] = [
            'name' => $name,
            'microtime' => $endTime,
            'context' => $context,
            'event' => $event,
        ];
    }

    /**
     * @return list<array{name: string, microtime: float, context: array<string, mixed>}>
     */
    public function all(): array
    {
        return $this->records;
    }

    /**
     * @return list<array{name: string, microtime: float, context: array<string, mixed>}>
     */
    public function recordsOf(string $eventName): array
    {
        $matched = [];

        foreach ($this->records as $record) {
            if ($record['name'] === $eventName) {
                $matched[] = $record;
            }
        }

        return $matched;
    }

    public function count(): int
    {
        return count($this->records);
    }

    public function isEmpty(): bool
    {
        return $this->records === [];
    }

    /**
     * Number of recorded events per {@see HasEventName::eventName()}.
     *
     * @return array<string, int>
     */
    public function countsByName(): array
    {
        $counts = [];

        foreach ($this->records as $record) {
            $name = $record['name'];
            $counts[$name] = ($counts[$name] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Timeline intervals for profilers and tracers (OpenTelemetry-style spans).
     *
     * {@see record()} stores the end timestamp; when {@see DescribesTelemetryContext::context()}
     * includes {@code duration_seconds}, {@code start} is computed as {@code end - duration}.
     *
     * @return list<array{
     *     name: string,
     *     start: float,
     *     end: float,
     *     duration: float,
     *     meta: array<string, mixed>,
     *     category: string
     * }>
     */
    public function spans(): array
    {
        $spans = [];

        foreach ($this->records as $record) {
            ['start' => $start, 'end' => $end, 'duration' => $duration] = $this->resolveSpanBounds(
                $record['microtime'],
                $record,
            );

            $spans[] = [
                'name' => $record['name'],
                'start' => $start,
                'end' => $end,
                'duration' => $duration,
                'meta' => $record['context'],
                'category' => $this->resolveCategory($record['name']),
            ];
        }

        return $spans;
    }

    /**
     * Aggregated snapshot for logs, API, or DebugBar.
     *
     * @return array{
     *     total: int,
     *     started_at: float|null,
     *     ended_at: float|null,
     *     wall_time_seconds: float|null,
     *     counts_by_name: array<string, int>,
     *     timeline: list<array{
     *         name: string,
     *         offset_ms: float,
     *         context: array<string, mixed>,
     *         duration_seconds: float|null
     *     }>
     * }
     */
    public function statistics(): array
    {
        $spans = $this->spans();

        if ($spans === []) {
            return [
                'total' => 0,
                'started_at' => null,
                'ended_at' => null,
                'wall_time_seconds' => null,
                'counts_by_name' => [],
                'timeline' => [],
            ];
        }

        $startedAt = $spans[0]['start'];
        $endedAt = $spans[array_key_last($spans)]['end'];

        $timeline = [];

        foreach ($spans as $span) {
            $timeline[] = [
                'name' => $span['name'],
                'offset_ms' => round(($span['end'] - $startedAt) * 1000, 3),
                'context' => $span['meta'],
                'duration_seconds' => $span['duration'] > 0.0 ? $span['duration'] : null,
            ];
        }

        return [
            'total' => count($spans),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'wall_time_seconds' => round($endedAt - $startedAt, 6),
            'counts_by_name' => $this->countsByName(),
            'timeline' => $timeline,
        ];
    }

    public function reset(): void
    {
        $this->records = [];
    }

    /**
     * @param float $end
     * @param array{name: string, microtime: float, context: array<string, mixed>, event?: object} $record
     * @return array{start: float, end: float, duration: float}
     */
    private function resolveSpanBounds(float $end, array $record): array
    {
        $event = $record['event'] ?? null;
        $context = $record['context'];

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

    private function resolveCategory(string $name): string
    {
        $category = explode('.', $name, 2)[0];

        return $category !== '' ? $category : 'app';
    }
}
