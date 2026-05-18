<?php declare(strict_types=1);

namespace Concept\Core\Events\View;

use Concept\Core\Events\Contracts\DescribesTelemetryContext;
use League\Event\HasEventName;

abstract class AbstractViewEvent implements HasEventName, DescribesTelemetryContext
{
    public function __construct(
        public readonly string $templateLogicalName,
    ) {}

    abstract public function eventName(): string;

    public function context(): array
    {
        return [
            'template' => $this->templateLogicalName,
        ];
    }
}
