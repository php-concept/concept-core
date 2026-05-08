<?php declare(strict_types=1);

namespace Concept\Core\Http\Middlewares;

use Concept\Core\Http\Protocol\HttpHeader;
use Concept\Core\Http\Protocol\HttpValue;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ParseJsonBodyMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $contentType = $request->getHeaderLine(HttpHeader::CONTENT_TYPE);
        if (!str_contains($contentType, HttpValue::JSON)) {
            return $handler->handle($request);
        }

        $contents = json_decode((string)$request->getBody(), true);
        if (json_last_error() === JSON_ERROR_NONE) {
            /** @var array<int|string, mixed>|object|null $contents */
            $request = $request->withParsedBody($contents);
        }

        return $handler->handle($request);
    }
}