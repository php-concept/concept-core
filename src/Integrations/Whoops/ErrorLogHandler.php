<?php declare(strict_types=1);

namespace Concept\Core\Integrations\Whoops;

use Concept\Core\Components\Container\ContainerResolver;
use Concept\Core\Components\Logger\Contracts\LoggerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Whoops\Handler\Handler;

class ErrorLogHandler extends Handler
{
    public function __construct(private readonly ContainerInterface $container) {}

    public function handle(): int
    {
        /** @var LoggerInterface|null $logger */
        $logger = ContainerResolver::tryGet($this->container, LoggerInterface::class);
        if ($logger === null) {
            return Handler::DONE;
        }

        try {
            $logger->exception($this->getException(), $this->getUri());
        } catch (Throwable) {
        }

        return Handler::DONE;
    }

    private function getUri(): string
    {
        $uri = '';
        if (isset($_SERVER['REQUEST_URI'])) {
            $uri = is_string($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'unknown';
            /** @var ServerRequestInterface|null $request */
            $request = ContainerResolver::tryGet($this->container, ServerRequestInterface::class);
            if ($request !== null) {
                $uri = $request->getUri()->getPath();
            }
        }

        return $uri;
    }
}
