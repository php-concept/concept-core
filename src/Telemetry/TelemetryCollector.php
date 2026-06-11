<?php declare(strict_types=1);

namespace Concept\Core\Telemetry;

use Concept\Core\Telemetry\Contracts\TelemetryItemInterface;

class TelemetryCollector
{
    /** @var array<string, array<string, TelemetryItemInterface>> */
    private array $telemetryItems = [];

    /**
     * @param array<mixed> $context
     */
    public function start(string $telemetryEventName, array $context = [], ?float $duration = null): string
    {
        $id = uniqid();
        $this->telemetryItems[$telemetryEventName][$id] = new TelemetryItem($telemetryEventName, $context, $duration);

        return $id;
    }

    public function finish(string $telemetryEventName, string $id): void
    {
        if (isset($this->telemetryItems[$telemetryEventName][$id])) {
            $this->telemetryItems[$telemetryEventName][$id]->finish();
        }
    }

    /**
     * @param array<mixed> $context
     */
    public function record(string $telemetryEventName, array $context = [], ?float $duration = null): string
    {
        $id = $this->start($telemetryEventName, $context, $duration);
        $this->finish($telemetryEventName, $id);

        return $id;
    }

    public function mark(string $telemetryEventName, string $name): void
    {
        $id = $this->start($telemetryEventName, [TelemetryKey::NAME => $name]);
        $this->finish($telemetryEventName, $id);
    }

    public function reset(): void
    {
        $this->telemetryItems = [];
    }

    /**
     * @return array<string, array<string, TelemetryItemInterface>>|array<string, TelemetryItemInterface>
     */
    public function items(?string $telemetryEventName = null): array
    {
        if ($telemetryEventName) {
            return $this->telemetryItems[$telemetryEventName] ?? [];
        }

        return $this->telemetryItems;
    }

    /**
     * @return array<array<string, mixed>>|array<string, array<array<string, mixed>>>
     */
    public function toArray(?string $telemetryEventName = null): array
    {
        if ($telemetryEventName !== null) {
            return array_map(
                static fn (TelemetryItemInterface $item): array => $item->toArray(),
                $this->telemetryItems[$telemetryEventName] ?? []
            );
        }

        $result = [];
        foreach ($this->telemetryItems as $eventName => $items) {
            $result[$eventName] = array_map(
                static fn (TelemetryItemInterface $item): array => $item->toArray(),
                $items
            );
        }

        return $result;
    }
}
