<?php declare(strict_types=1);

namespace Concept\Core\Events\Http;

use Concept\Core\Events\Contracts\DescribesTelemetryContext;
use Concept\Core\Events\EventName;
use League\Event\HasEventName;

final class FormRequestValidationFailed implements HasEventName, DescribesTelemetryContext
{
    /**
     * @param array<string, mixed> $errors
     */
    public function __construct(
        public readonly string $formRequestClass,
        public readonly array $errors,
    ) {}

    public function eventName(): string
    {
        return EventName::HTTP_FORM_REQUEST_VALIDATION_FAILED;
    }

    public function context(): array
    {
        return [
            'form_request' => $this->formRequestClass,
            'error_fields' => array_keys($this->errors),
        ];
    }
}
