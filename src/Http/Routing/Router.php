<?php declare(strict_types=1);

namespace Concept\Core\Http\Routing;

use Concept\Core\Http\Routing\Contracts\RouterInterface;
use League\Route\Router as LeagueRouter;

class Router extends LeagueRouter implements RouterInterface
{
}
