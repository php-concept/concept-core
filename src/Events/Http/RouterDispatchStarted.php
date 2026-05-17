<?php declare(strict_types=1);

namespace Concept\Core\Events\Http;

use Concept\Core\Events\Contracts\DescribesTelemetryContext;
use Concept\Core\Events\EventName;
use League\Event\HasEventName;
use Psr\Http\Message\ServerRequestInterface;

final class RouterDispatchStarted implements HasEventName, DescribesTelemetryContext
{
    public function __construct(
        public readonly ServerRequestInterface $request,
    ) {}

    public function eventName(): string
    {
        return EventName::HTTP_ROUTER_DISPATCH_STARTED;
    }

    public function context(): array
    {
        return [
            'method' => $this->request->getMethod(),
            'path' => $this->request->getUri()->getPath(),
        ];
    }
}
