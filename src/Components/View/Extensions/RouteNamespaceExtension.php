<?php

namespace Concept\Core\Components\View\Extensions;

use Concept\Core\Components\View\RouteNamespaceResolver;
use Twig\Extension\AbstractExtension;
use Twig\Extension\GlobalsInterface;

class RouteNamespaceExtension extends AbstractExtension implements GlobalsInterface
{
    private const string ROUTE_NAMESPACE = 'route_namespace';

    public function __construct(
        private readonly RouteNamespaceResolver $routeNamespaceResolver
    ) {}

    public function getGlobals(): array
    {
        return [
            self::ROUTE_NAMESPACE => $this->routeNamespaceResolver->resolve(),
        ];
    }
}