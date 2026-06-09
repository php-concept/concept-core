<?php declare(strict_types=1);

namespace Concept\Core\Http\Routing\Contracts;

use League\Route\Middleware\MiddlewareAwareInterface;
use League\Route\Route;
use League\Route\RouteCollectionInterface;
use League\Route\RouteGroup;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

interface RouterInterface extends RouteCollectionInterface, MiddlewareAwareInterface
{
    public function group(string $prefix, callable $group): RouteGroup;

    public function getNamedRoute(string $name): Route;

    public function dispatch(ServerRequestInterface $request): ResponseInterface;
}
