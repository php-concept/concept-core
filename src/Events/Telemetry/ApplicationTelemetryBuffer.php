<?php declare(strict_types=1);

namespace Concept\Core\Events\Telemetry;

use Concept\Core\Events\Contracts\DescribesTelemetryContext;
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
        $name = $event instanceof HasEventName ? $event->eventName() : $event::class;
        $context = $event instanceof DescribesTelemetryContext ? $event->context() : [];

        $this->records[] = [
            'name' => $name,
            'microtime' => microtime(true),
            'context' => $context,
        ];
    }

    /**
     * @return list<array{name: string, microtime: float, context: array<string, mixed>}>
     */
    public function all(): array
    {
        return $this->records;
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
        if ($this->records === []) {
            return [
                'total' => 0,
                'started_at' => null,
                'ended_at' => null,
                'wall_time_seconds' => null,
                'counts_by_name' => [],
                'timeline' => [],
            ];
        }

        $startedAt = $this->records[0]['microtime'];
        $endedAt = $this->records[array_key_last($this->records)]['microtime'];

        $timeline = [];

        foreach ($this->records as $record) {
            $duration = $record['context']['duration_seconds'] ?? null;

            $timeline[] = [
                'name' => $record['name'],
                'offset_ms' => round(($record['microtime'] - $startedAt) * 1000, 3),
                'context' => $record['context'],
                'duration_seconds' => is_numeric($duration) ? (float) $duration : null,
            ];
        }

        return [
            'total' => count($this->records),
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
}
