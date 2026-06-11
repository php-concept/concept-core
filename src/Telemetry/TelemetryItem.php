<?php declare(strict_types=1);

namespace Concept\Core\Telemetry;

use Concept\Core\Telemetry\Contracts\TelemetryItemInterface;

class TelemetryItem implements TelemetryItemInterface
{
    private const string NAME = 'name';
    private const string CONTEXT = 'context';
    private const string STARTED_AT = 'started_at';
    private const string FINISHED_AT = 'finished_at';
    private const string DURATION = 'duration';

    private ?float $startedAt = null;
    private ?float $finishedAt = null;

    /**
     * @param array<mixed> $context
     */
    public function __construct(
        private readonly string $name,
        private readonly array $context = [],
        private readonly ?float $duration = null
    ) {
        $this->startedAt = microtime(true);
    }

    public function finish(): void
    {
        if ($this->finishedAt === null) {
            $this->finishedAt = microtime(true);
        }
    }

    public function getName(): string { return $this->name; }

    public function getContext(): array { return $this->context; }

    public function getStartedAt(): ?float { return $this->startedAt; }

    public function getFinishedAt(): ?float { return $this->finishedAt; }

    public function getDuration(): ?float
    {
        if ($this->duration !== null) {
            return $this->duration;
        }

        if ($this->startedAt && $this->finishedAt) {
            return $this->finishedAt - $this->startedAt;
        }

        return null;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            self::NAME => $this->getName(),
            self::CONTEXT => $this->getContext(),
            self::STARTED_AT => $this->getStartedAt(),
            self::FINISHED_AT => $this->getFinishedAt(),
            self::DURATION => $this->getDuration(),
        ];
    }
}
