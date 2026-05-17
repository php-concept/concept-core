<?php declare(strict_types=1);

namespace Concept\Core\Events\Contracts;

interface DescribesTelemetryContext
{
    /**
     * @return array<string, mixed>
     */
    public function context(): array;
}
