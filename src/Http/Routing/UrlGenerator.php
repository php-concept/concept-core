<?php declare(strict_types=1);

namespace Concept\Core\Http\Routing;

use Concept\Core\Http\Routing\Contracts\RouterInterface;
use Concept\Core\Http\Routing\Contracts\UrlGeneratorInterface;
use Psr\Http\Message\ServerRequestInterface;

class UrlGenerator implements UrlGeneratorInterface
{
    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly RouterInterface $router
    ) {}

    public function base(string $uri = ''): string
    {
        $uri = $this->request->getUri();

        $baseUrl = $uri->getScheme() . '://' . $uri->getHost();

        $port = $uri->getPort();
        if ($port !== null && !in_array($port, [80, 443])) {
            $baseUrl .= ':' . $port;
        }

        return $baseUrl;
    }

    public function uri(string $name, array $parameters = []): string
    {
        return $this->router->getNamedRoute($name)->getPath($parameters);
    }

    public function url(string $name, array $parameters = []): string
    {
        return $this->build($this->base(), $this->uri($name, $parameters));
    }

    private function build(string $baseUrl, string $uri): string
    {
        return rtrim($baseUrl, '/') . '/' .  ltrim($uri, '/');
    }
}
