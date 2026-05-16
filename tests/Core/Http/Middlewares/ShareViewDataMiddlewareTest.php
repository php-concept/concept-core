<?php declare(strict_types=1);

namespace Tests\Core;

use Concept\Core\Components\Csrf\CsrfTokenManager;
use Concept\Core\Http\Middlewares\ShareViewDataMiddleware;
use Concept\Core\Http\RequestAttribute;
use Concept\Core\Http\SessionKey;
use Concept\Core\Http\ViewKey;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;
use Tests\Fixtures\Core\RecordingHandler;

final class ShareViewDataMiddlewareTest extends TestCase
{
    public function testAddsViewContextAttributeForDownstream(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $csrf = new CsrfTokenManager($session);
        $token = $csrf->getToken();

        $flash = $this->createStub(FlashBagInterface::class);
        $flash->method('get')->willReturnMap([
            [SessionKey::VALIDATION_ERRORS, [], ['email' => ['required']]],
            [SessionKey::VALIDATION_DATA, [], ['email' => 'a@b.test']],
        ]);
        $flash->method('all')->willReturn(['notice' => ['Welcome']]);

        $middleware = new ShareViewDataMiddleware($flash, $csrf);
        $inner = new RecordingHandler(new Response());

        $middleware->process(new ServerRequest(), $inner);

        /** @var array<string, mixed> $ctx */
        $ctx = $inner->request->getAttribute(RequestAttribute::VIEW_PAYLOAD);
        self::assertIsArray($ctx);
        self::assertSame(['email' => ['required']], $ctx[ViewKey::ERRORS]);
        self::assertSame(['email' => 'a@b.test'], $ctx[ViewKey::OLD_INPUT]);
        self::assertSame(['notice' => ['Welcome']], $ctx[ViewKey::FLASHES]);
        self::assertSame($token, $ctx[ViewKey::CSRF_TOKEN]);
    }
}
