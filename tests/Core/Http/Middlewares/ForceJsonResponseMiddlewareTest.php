<?php declare(strict_types=1);

namespace Tests\Core;

use Concept\Core\Http\Middlewares\ForceJsonResponseMiddleware;
use Concept\Core\Http\Protocol\HttpHeader;
use Concept\Core\Http\Protocol\HttpValue;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Core\RecordingHandler;

final class ForceJsonResponseMiddlewareTest extends TestCase
{
    public function testForcesAcceptHeaderForDownstreamAndJsonContentTypeOnResponse(): void
    {
        $handlerResponse = (new Response())->withHeader(HttpHeader::CONTENT_TYPE, HttpValue::HTML);
        $inner = new RecordingHandler($handlerResponse);
        $middleware = new ForceJsonResponseMiddleware();

        $request = new ServerRequest();

        $out = $middleware->process($request, $inner);

        self::assertSame(HttpValue::JSON, $inner->request->getHeaderLine(HttpHeader::ACCEPT));
        self::assertSame(HttpValue::JSON, $out->getHeaderLine(HttpHeader::CONTENT_TYPE));
    }
}
