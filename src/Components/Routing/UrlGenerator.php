<?php declare(strict_types=1);

namespace Concept\Core\Components\Routing;

use Concept\Core\Components\Routing\Contracts\UrlGeneratorInterface;
use League\Route\Router;

class UrlGenerator implements UrlGeneratorInterface
{
    public function __construct(
        private readonly Router $router
    ) {}

    public function route(string $name, array $parameters = []): string
    {
        return $this->router->getNamedRoute($name)->getPath($parameters);
    }
}