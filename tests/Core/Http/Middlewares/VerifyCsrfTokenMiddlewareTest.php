<?php declare(strict_types=1);

namespace Tests\Core;

use Concept\Core\Components\Csrf\CsrfTokenManager;
use Concept\Core\Http\Exceptions\Security\CsrfException;
use Concept\Core\Http\Middlewares\VerifyCsrfTokenMiddleware;
use Concept\Core\Http\Protocol\HttpHeader;
use Concept\Core\Http\Protocol\HttpMethod;
use Concept\Core\Http\SessionKey;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class VerifyCsrfTokenMiddlewareTest extends TestCase
{
    public function testIgnoresSafeMethods(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $middleware = new VerifyCsrfTokenMiddleware(new CsrfTokenManager($session));

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturn(new Response());

        $request = (new ServerRequest())->withMethod(HttpMethod::GET);

        $middleware->process($request, $handler);
    }

    public function testAllowsValidTokenInParsedBody(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $manager = new CsrfTokenManager($session);
        $token = $manager->getToken();
        $middleware = new VerifyCsrfTokenMiddleware($manager);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturn(new Response());

        $request = (new ServerRequest())
            ->withMethod(HttpMethod::POST)
            ->withParsedBody([SessionKey::CSRF_TOKEN => $token]);

        $middleware->process($request, $handler);
    }

    public function testAllowsValidTokenInHeader(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $manager = new CsrfTokenManager($session);
        $token = $manager->getToken();
        $middleware = new VerifyCsrfTokenMiddleware($manager);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturn(new Response());

        $request = (new ServerRequest())
            ->withMethod(HttpMethod::PUT)
            ->withHeader(HttpHeader::X_CSRF_TOKEN, $token);

        $middleware->process($request, $handler);
    }

    public function testAllowsUrlEncodedXsrfTokenHeader(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $manager = new CsrfTokenManager($session);
        $token = $manager->getToken();
        $middleware = new VerifyCsrfTokenMiddleware($manager);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturn(new Response());

        $request = (new ServerRequest())
            ->withMethod(HttpMethod::DELETE)
            ->withHeader(HttpHeader::X_XSRF_TOKEN, urlencode($token));

        $middleware->process($request, $handler);
    }

    public function testFallsBackToHeaderWhenBodyTokenIsNotString(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $manager = new CsrfTokenManager($session);
        $token = $manager->getToken();
        $middleware = new VerifyCsrfTokenMiddleware($manager);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::once())->method('handle')->willReturn(new Response());

        $request = (new ServerRequest())
            ->withMethod(HttpMethod::POST)
            ->withParsedBody([SessionKey::CSRF_TOKEN => ['unexpected' => 'shape']])
            ->withHeader(HttpHeader::X_CSRF_TOKEN, $token);

        $middleware->process($request, $handler);
    }

    public function testRejectsRequestWithoutAnyTokenSource(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $manager = new CsrfTokenManager($session);
        $manager->getToken();
        $middleware = new VerifyCsrfTokenMiddleware($manager);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $request = (new ServerRequest())->withMethod(HttpMethod::POST);

        $this->expectException(CsrfException::class);

        $middleware->process($request, $handler);
    }

    public function testRejectsInvalidToken(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $manager = new CsrfTokenManager($session);
        $manager->getToken();
        $middleware = new VerifyCsrfTokenMiddleware($manager);

        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $request = (new ServerRequest())
            ->withMethod(HttpMethod::POST)
            ->withParsedBody([SessionKey::CSRF_TOKEN => 'wrong']);

        $this->expectException(CsrfException::class);

        $middleware->process($request, $handler);
    }
}
