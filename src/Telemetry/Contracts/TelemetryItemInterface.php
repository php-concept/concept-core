<?php declare(strict_types=1);

namespace Concept\Core\Telemetry\Contracts;

interface TelemetryItemInterface
{
    public function getName(): string;

    /** @return array<mixed> */
    public function getContext(): array;

    public function getStartedAt(): ?float;

    public function getFinishedAt(): ?float;

    public function getDuration(): ?float;

    public function finish(): void;

    /** @return array<mixed> */
    public function toArray(): array;
}
