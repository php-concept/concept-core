<?php declare(strict_types=1);

namespace Concept\Core\Components\View;

use Concept\Core\Components\View\Registries\TwigRouteNamespaceRegistry;
use Psr\Http\Message\ServerRequestInterface;

class RouteNamespaceResolver
{
    public function __construct(
        private readonly ?ServerRequestInterface $request,
        private readonly TwigRouteNamespaceRegistry $viewRouteNamespaceRegistry
    ) {}

    public function resolve(): ?string
    {
        if (!$this->request) {
            return null;
        }
        $path = '/' . ltrim($this->request->getUri()->getPath(), '/');
        /** @var array<string, string> $namespacesMap */
        $namespacesMap = $this->viewRouteNamespaceRegistry->all();
        uksort($namespacesMap, fn($a, $b) => strlen($b) <=> strlen($a));

        foreach ($namespacesMap as $prefix => $namespace) {
            if (str_starts_with($path, $prefix)) {
                return $namespace;
            }
        }

        return !empty($namespacesMap) ? reset($namespacesMap) : null;
    }
}