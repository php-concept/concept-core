<?php declare(strict_types=1);

namespace Concept\Core\Events\Telemetry\Internal;

/**
 * @internal
 */
final readonly class Span
{
    /**
     * @param array<string, mixed> $meta
     */
    public function __construct(
        public string $name,
        public float $start,
        public float $end,
        public float $duration,
        public array $meta,
        public string $category,
    ) {
    }
}
