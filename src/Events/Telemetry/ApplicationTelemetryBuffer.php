<?php declare(strict_types=1);

namespace Concept\Core\Events\Telemetry;

use Concept\Core\Events\Contracts\TimedEventInterface;
use Concept\Core\Events\Contracts\DictionaryEventInterface;
use Concept\Core\Events\Database\QueryExecuted;
use Concept\Core\Events\Telemetry\Internal\Span;
use Concept\Core\Events\Telemetry\Internal\TelemetryRecord;
use League\Event\HasEventName;

/**
 * Collects structured events for in-request inspection (e.g. DebugBar tab, log enrichment).
 */
final class ApplicationTelemetryBuffer
{
    /**
     * @var list<TelemetryRecord>
     */
    private array $records = [];

    public function record(object $event): void
    {
        $this->records[] = new TelemetryRecord($event);
    }

    /**
     * @return list<TelemetryRecord>
     */
    public function all(): array
    {
        return $this->records;
    }

    /**
     * @return list<TelemetryRecord>
     */
    public function recordsOf(string $eventName): array
    {
        return array_values(array_filter(
            $this->records,
            fn(TelemetryRecord $record) => $record->name === $eventName
        ));
    }

    /**
     * @return list<array{sql: string, bindings: array<mixed>, time: float, connection: string}>
     */
    public function queryRecords(): array
    {
        $queries = [];

        foreach ($this->records as $record) {
            $event = $record->event;
            if ($event instanceof QueryExecuted) {
                $queries[] = $event->context();
            }
        }

        return $queries;
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
            $counts[$record->name] = ($counts[$record->name] ?? 0) + 1;
        }

        return $counts;
    }

    /**
     * Timeline intervals for profilers and tracers (OpenTelemetry-style spans).
     *
     * @return list<Span>
     */
    public function spans(): array
    {
        $spans = [];

        foreach ($this->records as $record) {
            if ($record->event instanceof DictionaryEventInterface) {
                continue;
            }

            $span = $record->toSpan($this->resolveCategory($record->name));

            if ($span->duration === 0.0 && !($record->event instanceof TimedEventInterface)) {
                continue;
            }

            $spans[] = $span;
        }

        return $spans;
    }

    /**
     * Dictionary data for static metadata (e.g. lists of services, components).
     *
     * @return array<string, list<string>|array<string, mixed>>
     */
    public function dictionary(): array
    {
        $dictionary = [];

        foreach ($this->records as $record) {
            $event = $record->event;

            if ($event instanceof DictionaryEventInterface) {
                $type = $event->dictionaryType();
                $label = $event->dictionaryLabel();
                $data = $event->dictionaryData();

                if ($data !== null) {
                    if (isset($dictionary[$type][$label]) && is_array($dictionary[$type][$label])) {
                        $dictionary[$type][$label] = array_merge($dictionary[$type][$label], $data);
                    } else {
                        $dictionary[$type][$label] = $data;
                    }
                } else {
                    $dictionary[$type][] = $label;
                }
            }
        }

        ksort($dictionary);

        return $dictionary;
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
     *     }>,
     *     dictionary: array<string, list<string>|array<string, mixed>>
     * }
     */
    public function statistics(): array
    {
        $spans = $this->spans();
        $dictionary = $this->dictionary();

        if ($spans === []) {
            return [
                'total' => count($this->records),
                'started_at' => null,
                'ended_at' => null,
                'wall_time_seconds' => null,
                'counts_by_name' => $this->countsByName(),
                'timeline' => [],
                'dictionary' => $dictionary,
            ];
        }

        $startedAt = $spans[0]->start;
        $endedAt = $spans[array_key_last($spans)]->end;

        $timeline = [];

        foreach ($spans as $span) {
            $timeline[] = [
                'name' => $span->name,
                'offset_ms' => round(($span->end - $startedAt) * 1000, 3),
                'context' => $span->meta,
                'duration_seconds' => $span->duration > 0.0 ? $span->duration : null,
            ];
        }

        return [
            'total' => count($this->records),
            'started_at' => $startedAt,
            'ended_at' => $endedAt,
            'wall_time_seconds' => round($endedAt - $startedAt, 6),
            'counts_by_name' => $this->countsByName(),
            'timeline' => $timeline,
            'dictionary' => $dictionary,
        ];
    }

    public function reset(): void
    {
        $this->records = [];
    }

    private function resolveCategory(string $name): string
    {
        $category = explode('.', $name, 2)[0];

        return $category !== '' ? $category : 'app';
    }
}
