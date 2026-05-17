<?php declare(strict_types=1);

namespace Concept\Core\Events\Http;

use Concept\Core\Events\Contracts\DescribesTelemetryContext;
use Concept\Core\Events\EventName;
use League\Event\HasEventName;

final class RouteCallableInvoking implements HasEventName, DescribesTelemetryContext
{
    /**
     * @param list<string> $methods
     * @param array<string, string> $routeVars
     */
    public function __construct(
        public readonly array $methods,
        public readonly string $path,
        public readonly string $handler,
        public readonly array $routeVars,
    ) {}

    public function eventName(): string
    {
        return EventName::HTTP_ROUTE_CALLABLE_INVOKING;
    }

    public function context(): array
    {
        return [
            'methods' => $this->methods,
            'path' => $this->path,
            'handler' => $this->handler,
            'route_vars' => $this->routeVars,
        ];
    }
}
