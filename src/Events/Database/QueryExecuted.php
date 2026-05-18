<?php declare(strict_types=1);

namespace Concept\Core\Events\Database;

use Concept\Core\Events\Contracts\TimedEventInterface;
use Concept\Core\Events\EventName;
use League\Event\HasEventName;

final class QueryExecuted implements HasEventName, TimedEventInterface
{
    /**
     * @param string $sql
     * @param array<mixed> $bindings
     * @param float $time The time in milliseconds
     * @param string $connectionName
     */
    public function __construct(
        public readonly string $sql,
        public readonly array $bindings,
        public readonly float $time,
        public readonly string $connectionName,
    ) {}

    public function eventName(): string
    {
        return EventName::DATABASE_QUERY_EXECUTED;
    }

    /**
     * @return array{sql: string, bindings: array<mixed>, time: float, connection: string}
     */
    public function context(): array
    {
        return [
            'sql' => $this->sql,
            'bindings' => $this->bindings,
            'time' => $this->time,
            'connection' => $this->connectionName,
        ];
    }

    public function getDurationSeconds(): float
    {
        return $this->time / 1000;
    }

    public function getStartTime(): ?float
    {
        return null;
    }

    public function getEndTime(): ?float
    {
        return null;
    }
}
