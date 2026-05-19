<?php declare(strict_types=1);

namespace Concept\Core\Http;

use Concept\Core\Http\Protocol\HttpHeader;
use Concept\Core\Http\Protocol\HttpValue;
use Psr\Http\Message\ServerRequestInterface;

class RequestFormat
{
    public function expectsJson(ServerRequestInterface $request): bool
    {
        $accept = $request->getHeaderLine(HttpHeader::ACCEPT);
        $xhr = $request->getHeaderLine(HttpHeader::X_REQUESTED_WITH);

        return str_contains($accept, HttpValue::JSON) || $xhr === HttpValue::XML_HTTP_REQUEST;
    }

    public function expectsHtml(ServerRequestInterface $request): bool
    {
        return str_contains($request->getHeaderLine(HttpHeader::ACCEPT), HttpValue::HTML);
    }
}