<?php declare(strict_types=1);

namespace Concept\Core\Components\Telemetry;

use Concept\Core\Components\Telemetry\Contracts\TelemetryItemInterface;

class TelemetryCollector
{
    private const NAME_KEY = 'name';

    /** @var array<string, array<string, TelemetryItemInterface>> */
    private array $telemetryItems = [];

    /**
     * @param string $telemetryEventName
     * @param array<mixed> $context
     * @param float|null $duration
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

    public function mark(string $telemetryEventName, string $name): void
    {
        $id = $this->start($telemetryEventName, [self::NAME_KEY => $name]);
        $this->finish($telemetryEventName, $id);
    }

    /**
     * @param string|null $telemetryEventName
     * @return array|TelemetryItemInterface[]|Contracts\TelemetryItemInterface[][]|string[]
     */
    public function items(?string $telemetryEventName = null): array
    {
        if ($telemetryEventName) {
            return $this->telemetryItems[$telemetryEventName] ?? [];
        }

        return $this->telemetryItems;
    }

    /**
     * @return array<array<string, mixed>>
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