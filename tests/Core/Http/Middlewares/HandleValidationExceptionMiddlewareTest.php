<?php declare(strict_types=1);

namespace Tests\Core;

use Concept\Core\Components\Validator\Exceptions\ValidationException;
use Concept\Core\Http\Middlewares\HandleValidationExceptionMiddleware;
use Concept\Core\Http\Protocol\HttpHeader;
use Concept\Core\Http\Protocol\HttpStatusCode;
use Concept\Core\Http\Protocol\HttpValue;
use Concept\Core\Http\RequestFormat;
use Concept\Core\Http\ResponseFactory;
use Concept\Core\Http\SessionKey;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

final class HandleValidationExceptionMiddlewareTest extends TestCase
{
    public function testJsonRequestReturnsJsonErrorPayload(): void
    {
        $request = (new ServerRequest())
            ->withUri(new Uri('https://app.test/api'))
            ->withHeader(HttpHeader::ACCEPT, HttpValue::JSON);

        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects(self::once())
            ->method('jsonError')
            ->with(
                'Validation failed',
                HttpStatusCode::UNPROCESSABLE_ENTITY,
                ['title' => ['required']]
            )
            ->willReturn((new Response())->withStatus(HttpStatusCode::UNPROCESSABLE_ENTITY));

        $requestFormat = new RequestFormat();
        $flash = $this->createMock(FlashBagInterface::class);
        $flash->expects(self::never())->method('set');

        $middleware = new HandleValidationExceptionMiddleware($responseFactory, $requestFormat, $flash);

        $handler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                throw new ValidationException(['title' => ['required']], ['title' => '']);
            }
        };

        $response = $middleware->process($request, $handler);

        self::assertSame(HttpStatusCode::UNPROCESSABLE_ENTITY, $response->getStatusCode());
    }

    public function testHtmlRequestFlashesAndRedirectsBack(): void
    {
        $request = (new ServerRequest())->withUri(new Uri('https://app.test/form'));

        $back = (new Response())->withStatus(HttpStatusCode::FOUND)->withHeader('Location', '/prev');
        $responseFactory = $this->createMock(ResponseFactory::class);
        $responseFactory->expects(self::once())->method('back')->willReturn($back);
        $responseFactory->expects(self::never())->method('jsonError');

        $requestFormat = new RequestFormat();

        $sets = [];
        $flash = $this->createStub(FlashBagInterface::class);
        $flash->method('set')->willReturnCallback(function (string $k, mixed $v) use (&$sets): void {
            $sets[$k] = $v;
        });

        $middleware = new HandleValidationExceptionMiddleware($responseFactory, $requestFormat, $flash);

        $handler = new class implements RequestHandlerInterface {
            public function handle(\Psr\Http\Message\ServerRequestInterface $request): ResponseInterface
            {
                throw new ValidationException(['x' => ['bad']], ['x' => 'old']);
            }
        };

        $response = $middleware->process($request, $handler);

        self::assertSame($back, $response);
        self::assertSame('Validation failed', $sets['error']);
        self::assertSame(['x' => ['bad']], $sets[SessionKey::VALIDATION_ERRORS]);
        self::assertSame(['x' => 'old'], $sets[SessionKey::VALIDATION_DATA]);
    }
}
