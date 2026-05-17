<?php declare(strict_types=1);

namespace Concept\Core\Events\Http;

use Concept\Core\Events\Contracts\DescribesTelemetryContext;
use Concept\Core\Events\EventName;
use League\Event\HasEventName;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final class RouterDispatchFinished implements HasEventName, DescribesTelemetryContext
{
    public function __construct(
        public readonly ServerRequestInterface $request,
        public readonly ?ResponseInterface $response,
        public readonly float $durationSeconds,
    ) {}

    public function eventName(): string
    {
        return EventName::HTTP_ROUTER_DISPATCH_FINISHED;
    }

    public function context(): array
    {
        $status = null;
        if ($this->response !== null) {
            $status = $this->response->getStatusCode();
        }

        return [
            'method' => $this->request->getMethod(),
            'path' => $this->request->getUri()->getPath(),
            'status' => $status,
            'duration_seconds' => $this->durationSeconds,
        ];
    }
}
