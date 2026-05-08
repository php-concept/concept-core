<?php declare(strict_types=1);

namespace Tests\Core;

use Concept\Core\Http\Protocol\HttpHeader;
use Concept\Core\Http\Protocol\HttpValue;
use Concept\Core\Http\RequestFormat;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class RequestFormatTest extends TestCase
{
    public function testExpectsJsonWhenAcceptContainsJson(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getHeaderLine')->willReturnMap([
            [HttpHeader::ACCEPT, 'application/json'],
            [HttpHeader::X_REQUESTED_WITH, ''],
        ]);

        $format = new RequestFormat();

        self::assertTrue($format->expectsJson($request));
    }

    public function testExpectsJsonWhenXmlHttpRequestHeaderPresent(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getHeaderLine')->willReturnMap([
            [HttpHeader::ACCEPT, 'text/html'],
            [HttpHeader::X_REQUESTED_WITH, HttpValue::XML_HTTP_REQUEST],
        ]);

        $format = new RequestFormat();

        self::assertTrue($format->expectsJson($request));
    }

    public function testExpectsJsonReturnsFalseForRegularHtmlRequest(): void
    {
        $request = $this->createStub(ServerRequestInterface::class);
        $request->method('getHeaderLine')->willReturnMap([
            [HttpHeader::ACCEPT, 'text/html'],
            [HttpHeader::X_REQUESTED_WITH, ''],
        ]);

        $format = new RequestFormat();

        self::assertFalse($format->expectsJson($request));
    }
}
