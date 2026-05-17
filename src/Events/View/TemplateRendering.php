<?php declare(strict_types=1);

namespace Concept\Core\Events\View;

use Concept\Core\Events\Contracts\DescribesTelemetryContext;
use Concept\Core\Events\EventName;
use League\Event\HasEventName;

final class TemplateRendering implements HasEventName, DescribesTelemetryContext
{
    public function __construct(
        public readonly string $templateLogicalName,
    ) {}

    public function eventName(): string
    {
        return EventName::VIEW_TEMPLATE_RENDERING;
    }

    public function context(): array
    {
        return [
            'template' => $this->templateLogicalName,
        ];
    }
}
