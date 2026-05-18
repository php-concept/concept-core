<?php declare(strict_types=1);

namespace Concept\Core\Events\View;

use Concept\Core\Events\Contracts\TimedEventInterface;
use Concept\Core\Events\EventName;

final class TemplateRendered extends AbstractViewEvent implements TimedEventInterface
{
    public function __construct(
        string $templateLogicalName,
        public readonly float $durationSeconds,
    ) {
        parent::__construct($templateLogicalName);
    }

    public function eventName(): string
    {
        return EventName::VIEW_TEMPLATE_RENDERED;
    }

    public function context(): array
    {
        return array_merge(parent::context(), [
            'duration_seconds' => $this->durationSeconds,
        ]);
    }

    public function getDurationSeconds(): float
    {
        return $this->durationSeconds;
    }

    public function getStartTime(): ?float
    {
        return null;
    }

    public function getEndTime(): ?float
    {
        return null;
    }
}
