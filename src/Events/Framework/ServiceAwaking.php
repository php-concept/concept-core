<?php declare(strict_types=1);

namespace Concept\Core\Events\Framework;

use Concept\Core\Events\Contracts\DescribesTelemetryContext;
use Concept\Core\Events\EventName;
use League\Event\HasEventName;

final class ServiceAwaking implements HasEventName, DescribesTelemetryContext
{
    public function __construct(
        public readonly string $serviceName,
    ) {}

    public function eventName(): string
    {
        return EventName::FRAMEWORK_SERVICE_AWAKENING;
    }

    public function context(): array
    {
        return [
            'service' => $this->serviceName,
        ];
    }
}
