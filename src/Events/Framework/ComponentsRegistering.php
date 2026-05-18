<?php declare(strict_types=1);

namespace Concept\Core\Events\Framework;

use Concept\Core\Components\Component\Contracts\ComponentInterface;
use Concept\Core\Events\Contracts\DescribesTelemetryContext;
use Concept\Core\Events\EventName;
use League\Event\HasEventName;

final class ComponentsRegistering implements HasEventName, DescribesTelemetryContext
{
    /**
     * @param array<ComponentInterface> $components
     */
    public function __construct(
        public readonly array $components,
    ) {}

    public function eventName(): string
    {
        return EventName::FRAMEWORK_COMPONENT_PROVIDER_REGISTERING;
    }

    public function context(): array
    {
        return [
            'components' => array_map(fn($component) => get_class($component), $this->components),
        ];
    }
}
