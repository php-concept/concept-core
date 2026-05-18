<?php declare(strict_types=1);

namespace Concept\Core\Events\Contracts;

/**
 * Events that provide timing information for telemetry.
 */
interface TimedEventInterface
{
    public function getDurationSeconds(): ?float;

    public function getStartTime(): ?float;

    public function getEndTime(): ?float;
}
