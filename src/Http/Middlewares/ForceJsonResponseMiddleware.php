<?php declare(strict_types=1);

namespace Concept\Core\Http\Middlewares;

use Concept\Core\Http\Protocol\HttpHeader;
use Concept\Core\Http\Protocol\HttpValue;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class ForceJsonResponseMiddleware implements MiddlewareInterface
{
    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        // Set Accept header for internal Core logic
        $request = $request->withHeader(HttpHeader::ACCEPT, HttpValue::JSON);

        $response = $handler->handle($request);

        // Guaranteed to return a response with the desired content type (e.g. JSON)
        return $response->withHeader(HttpHeader::CONTENT_TYPE, HttpValue::JSON);
    }
}