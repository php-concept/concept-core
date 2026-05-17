<?php declare(strict_types=1);

namespace Concept\Core\Events\View;

use Concept\Core\Events\Contracts\DescribesTelemetryContext;
use Concept\Core\Events\EventName;
use League\Event\HasEventName;

final class TemplateRendered implements HasEventName, DescribesTelemetryContext
{
    public function __construct(
        public readonly string $templateLogicalName,
        public readonly float $durationSeconds,
    ) {}

    public function eventName(): string
    {
        return EventName::VIEW_TEMPLATE_RENDERED;
    }

    public function context(): array
    {
        return [
            'template' => $this->templateLogicalName,
            'duration_seconds' => $this->durationSeconds,
        ];
    }
}
