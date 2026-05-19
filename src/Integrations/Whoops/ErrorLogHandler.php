<?php declare(strict_types=1);

namespace Concept\Core\Integrations\Whoops;

use Concept\Core\Components\Logger\Contracts\LoggerInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Whoops\Handler\Handler;

class ErrorLogHandler extends Handler
{
    public function __construct(private readonly ContainerInterface $container) {}

    public function handle(): int
    {
        if (!$this->container->has(LoggerInterface::class)) {
            return Handler::DONE;
        }

        /** @var LoggerInterface $logger **/
        $logger = $this->container->get(LoggerInterface::class);

        $logger->exception($this->getException(), $this->getUri());

        return Handler::DONE;
    }

    private function getUri(): string
    {
        $uri = '';
        if (isset($_SERVER['REQUEST_URI'])) {
            // Check if we have a request object, otherwise fallback to $_SERVER
            $uri = is_string($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'unknown';
            if ($this->container->has(ServerRequestInterface::class)) {
                /** @var ServerRequestInterface $request */
                $request = $this->container->get(ServerRequestInterface::class);
                $uri = $request->getUri()->getPath();
            }
        }

        return $uri;
    }
}