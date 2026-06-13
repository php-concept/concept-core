<?php declare(strict_types=1);

namespace Concept\Core\Http;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;

/**
 * Accessor to the current PSR-7 request stored in the container.
 * Inject into shared services instead of ServerRequestInterface.
 */
class RequestProxy
{
    public function __construct(private readonly ContainerInterface $container) {}

    public function get(): ServerRequestInterface
    {
        /** @var ServerRequestInterface $request */
        $request = $this->container->get(ServerRequestInterface::class);

        return $request;
    }
}
