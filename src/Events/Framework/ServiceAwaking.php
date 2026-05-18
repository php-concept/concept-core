<?php declare(strict_types=1);

namespace Concept\Core\Events\Framework;

use Concept\Core\Events\Contracts\DescribesTelemetryContext;
use Concept\Core\Events\Contracts\DictionaryEventInterface;
use Concept\Core\Events\EventName;
use League\Event\HasEventName;

final class ServiceAwaking implements HasEventName, DescribesTelemetryContext, DictionaryEventInterface
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

    public function dictionaryType(): string
    {
        return 'services';
    }

    public function dictionaryLabel(): string
    {
        return $this->serviceName;
    }

    public function dictionaryData(): ?array
    {
        return null;
    }
}
