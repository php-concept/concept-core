<?php declare(strict_types=1);

namespace Tests\Core;

use Concept\Core\Http\Middlewares\StorePreviousUrlMiddleware;
use Concept\Core\Http\Protocol\HttpHeader;
use Concept\Core\Http\Protocol\HttpMethod;
use Concept\Core\Http\Protocol\HttpValue;
use Concept\Core\Http\RequestAttribute;
use Concept\Core\Http\SessionKey;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tests\Fixtures\Core\RecordingHandler;

final class StorePreviousUrlMiddlewareTest extends TestCase
{
    public function testTracksCurrentAndPreviousUrlOnSequentialGetRequests(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $middleware = new StorePreviousUrlMiddleware($session);
        $response = new Response();

        $first = (new ServerRequest())
            ->withMethod(HttpMethod::GET)
            ->withUri(new Uri('https://app.test/one'));
        $middleware->process($first, new RecordingHandler($response));

        self::assertSame('https://app.test/one', $session->get(SessionKey::CURRENT_URL));
        self::assertNull($session->get(SessionKey::PREVIOUS_URL));

        $second = (new ServerRequest())
            ->withMethod(HttpMethod::GET)
            ->withUri(new Uri('https://app.test/two'));
        $inner = new RecordingHandler($response);
        $middleware->process($second, $inner);

        self::assertSame('https://app.test/two', $session->get(SessionKey::CURRENT_URL));
        self::assertSame('https://app.test/one', $session->get(SessionKey::PREVIOUS_URL));
        self::assertSame('https://app.test/one', $inner->request->getAttribute(RequestAttribute::SAFE_BACK_URL));
    }

    public function testPostUsesCurrentUrlAsSafeBack(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set(SessionKey::CURRENT_URL, 'https://app.test/form');
        $middleware = new StorePreviousUrlMiddleware($session);

        $request = (new ServerRequest())
            ->withMethod(HttpMethod::POST)
            ->withUri(new Uri('https://app.test/submit'));

        $inner = new RecordingHandler(new Response());
        $middleware->process($request, $inner);

        self::assertSame('https://app.test/form', $inner->request->getAttribute(RequestAttribute::SAFE_BACK_URL));
    }

    public function testAjaxGetDoesNotShiftSessionUrls(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set(SessionKey::CURRENT_URL, 'https://app.test/page');
        $middleware = new StorePreviousUrlMiddleware($session);

        $request = (new ServerRequest())
            ->withMethod(HttpMethod::GET)
            ->withHeader(HttpHeader::X_REQUESTED_WITH, HttpValue::XML_HTTP_REQUEST)
            ->withUri(new Uri('https://app.test/ajax-data'));

        $middleware->process($request, new RecordingHandler(new Response()));

        self::assertSame('https://app.test/page', $session->get(SessionKey::CURRENT_URL));
    }
}
