<?php declare(strict_types=1);

namespace Concept\Core\Events\Http;

use Concept\Core\Events\Contracts\DescribesTelemetryContext;
use Concept\Core\Events\EventName;
use League\Event\HasEventName;

final class FormRequestValidated implements HasEventName, DescribesTelemetryContext
{
    public function __construct(
        public readonly string $formRequestClass,
        public readonly float $durationSeconds,
    ) {}

    public function eventName(): string
    {
        return EventName::HTTP_FORM_REQUEST_VALIDATED;
    }

    public function context(): array
    {
        return [
            'form_request' => $this->formRequestClass,
            'duration_seconds' => $this->durationSeconds,
        ];
    }
}
