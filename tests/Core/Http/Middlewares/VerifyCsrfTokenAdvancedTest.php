<?php declare(strict_types=1);

namespace Tests\Core\Http\Middlewares;

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

final class VerifyCsrfTokenAdvancedTest extends TestCase
{
    /**
     * Verifies that CSRF validation fails if the session is cleared/regenerated 
     * even if a previously valid token is provided.
     */
    public function testFailsWhenSessionTokenWasRegenerated(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $manager = new CsrfTokenManager($session);
        
        $oldToken = $manager->getToken();
        
        // Simulate session regeneration or manual token removal
        $session->remove(SessionKey::CSRF_TOKEN);
        $newToken = $manager->getToken(); // generates a new one
        
        $middleware = new VerifyCsrfTokenMiddleware($manager);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        $request = (new ServerRequest())
            ->withMethod(HttpMethod::POST)
            ->withParsedBody([SessionKey::CSRF_TOKEN => $oldToken]);

        $this->expectException(CsrfException::class);
        $middleware->process($request, $handler);
    }

    /**
     * Verifies that X-XSRF-TOKEN header is correctly handled even with complex encoding
     * and fails if the decoded value is wrong.
     */
    public function testFailsWithIncorrectlyDecodedXsrfToken(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $manager = new CsrfTokenManager($session);
        $manager->getToken();
        
        $middleware = new VerifyCsrfTokenMiddleware($manager);
        $handler = $this->createMock(RequestHandlerInterface::class);
        $handler->expects(self::never())->method('handle');

        // wrong token, but URL encoded
        $request = (new ServerRequest())
            ->withMethod(HttpMethod::POST)
            ->withHeader(HttpHeader::X_XSRF_TOKEN, urlencode('invalid-token'));

        $this->expectException(CsrfException::class);
        $middleware->process($request, $handler);
    }
}
