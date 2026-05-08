<?php declare(strict_types=1);

namespace Tests\Core;

use Concept\Core\Http\Middlewares\ParseJsonBodyMiddleware;
use Concept\Core\Http\Protocol\HttpHeader;
use Concept\Core\Http\Protocol\HttpValue;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Core\RecordingHandler;

final class ParseJsonBodyMiddlewareTest extends TestCase
{
    public function testSkipsNonJsonContentType(): void
    {
        $inner = new RecordingHandler(new Response());
        $middleware = new ParseJsonBodyMiddleware();

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader(HttpHeader::CONTENT_TYPE, HttpValue::HTML)
            ->withBody($this->jsonStream('{"a":1}'));

        $middleware->process($request, $inner);

        self::assertNull($inner->request->getParsedBody());
    }

    public function testDecodesJsonBodyIntoParsedBody(): void
    {
        $inner = new RecordingHandler(new Response());
        $middleware = new ParseJsonBodyMiddleware();

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withHeader(HttpHeader::CONTENT_TYPE, 'application/json; charset=utf-8')
            ->withBody($this->jsonStream('{"name":"Ada","n":2}'));

        $middleware->process($request, $inner);

        self::assertSame(['name' => 'Ada', 'n' => 2], $inner->request->getParsedBody());
    }

    public function testInvalidJsonLeavesParsedBodyUnchanged(): void
    {
        $inner = new RecordingHandler(new Response());
        $middleware = new ParseJsonBodyMiddleware();

        $request = (new ServerRequest())
            ->withMethod('POST')
            ->withParsedBody(['existing' => true])
            ->withHeader(HttpHeader::CONTENT_TYPE, HttpValue::JSON)
            ->withBody($this->jsonStream('not-json'));

        $middleware->process($request, $inner);

        self::assertSame(['existing' => true], $inner->request->getParsedBody());
    }

    private function jsonStream(string $json): Stream
    {
        $stream = new Stream('php://memory', 'wb+');
        $stream->write($json);
        $stream->rewind();

        return $stream;
    }
}
