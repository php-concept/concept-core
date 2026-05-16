<?php declare(strict_types=1);

namespace Tests\Core;

use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Http\Protocol\HttpHeader;
use Concept\Core\Http\Protocol\HttpStatusCode;
use Concept\Core\Http\Protocol\HttpValue;
use Concept\Core\Http\RequestAttribute;
use Concept\Core\Http\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Fixtures\Core\ArrayContainer;

final class ResponseFactoryTest extends TestCase
{
    public function testCreateResponseSetsStatus(): void
    {
        $factory = $this->makeFactory(new ServerRequest(), $this->createStub(ViewInterface::class));

        $response = $factory->createResponse(HttpStatusCode::CREATED, 'Created');

        self::assertSame(HttpStatusCode::CREATED, $response->getStatusCode());
        self::assertSame('Created', $response->getReasonPhrase());
    }

    public function testJsonSetsBodyAndContentType(): void
    {
        $factory = $this->makeFactory(new ServerRequest(), $this->createStub(ViewInterface::class));

        $response = $factory->json(['ok' => true]);

        self::assertSame(HttpStatusCode::OK, $response->getStatusCode());
        self::assertSame(HttpValue::JSON, $response->getHeaderLine(HttpHeader::CONTENT_TYPE));
        self::assertStringContainsString('"ok":true', (string) $response->getBody());
    }

    public function testJsonSuccessWrapsPayload(): void
    {
        $factory = $this->makeFactory(new ServerRequest(), $this->createStub(ViewInterface::class));

        $response = $factory->jsonSuccess(['id' => 5]);
        $decoded = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('success', $decoded['status']);
        self::assertSame(HttpStatusCode::OK, $decoded['code']);
        self::assertSame(['id' => 5], $decoded['data']);
    }

    public function testJsonErrorIncludesMessageAndOptionalErrors(): void
    {
        $factory = $this->makeFactory(new ServerRequest(), $this->createStub(ViewInterface::class));

        $response = $factory->jsonError('Bad', HttpStatusCode::BAD_REQUEST, ['field' => ['required']]);
        $decoded = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(HttpStatusCode::BAD_REQUEST, $response->getStatusCode());
        self::assertSame('error', $decoded['status']);
        self::assertSame(HttpStatusCode::BAD_REQUEST, $decoded['code']);
        self::assertSame('Bad', $decoded['message']);
        self::assertSame(['field' => ['required']], $decoded['errors']);
    }

    public function testJsonErrorOmitsErrorsKeyWhenEmpty(): void
    {
        $factory = $this->makeFactory(new ServerRequest(), $this->createStub(ViewInterface::class));

        $response = $factory->jsonError('Oops');
        $decoded = json_decode((string) $response->getBody(), true, 512, JSON_THROW_ON_ERROR);

        self::assertArrayNotHasKey('errors', $decoded);
    }

    public function testRedirectReturnsLocation(): void
    {
        $factory = $this->makeFactory(new ServerRequest(), $this->createStub(ViewInterface::class));

        $response = $factory->redirect('/next', HttpStatusCode::FOUND);

        self::assertSame(HttpStatusCode::FOUND, $response->getStatusCode());
        self::assertSame('/next', $response->getHeaderLine('Location'));
    }

    public function testBackUsesInternalReferer(): void
    {
        $uri = new Uri('https://app.test/page');
        $request = (new ServerRequest())
            ->withUri($uri)
            ->withHeader(HttpHeader::REFERER, 'https://app.test/prev');

        $factory = $this->makeFactory($request, $this->createStub(ViewInterface::class));
        $response = $factory->back();

        self::assertSame('https://app.test/prev', $response->getHeaderLine('Location'));
    }

    public function testBackUsesSafeBackUrlWhenRefererMissing(): void
    {
        $uri = new Uri('https://app.test/page');
        $request = (new ServerRequest())
            ->withUri($uri)
            ->withAttribute(RequestAttribute::SAFE_BACK_URL, '/safe');

        $factory = $this->makeFactory($request, $this->createStub(ViewInterface::class));
        $response = $factory->back();

        self::assertSame('/safe', $response->getHeaderLine('Location'));
    }

    public function testBackPrefersRefererOverSafeBackUrl(): void
    {
        $uri = new Uri('https://app.test/page');
        $request = (new ServerRequest())
            ->withUri($uri)
            ->withHeader(HttpHeader::REFERER, '/from-referer')
            ->withAttribute(RequestAttribute::SAFE_BACK_URL, '/from-attribute');

        $factory = $this->makeFactory($request, $this->createStub(ViewInterface::class));
        $response = $factory->back();

        self::assertSame('/from-referer', $response->getHeaderLine('Location'));
    }

    public function testBackFallsBackWhenRefererAndSafeAttributeAreEmpty(): void
    {
        $uri = new Uri('https://app.test/page');
        $request = (new ServerRequest())
            ->withUri($uri)
            ->withAttribute(RequestAttribute::SAFE_BACK_URL, '');

        $factory = $this->makeFactory($request, $this->createStub(ViewInterface::class));
        $response = $factory->back(HttpStatusCode::FOUND, '/home');

        self::assertSame('/home', $response->getHeaderLine('Location'));
    }

    public function testBackFallsBackForExternalReferer(): void
    {
        $uri = new Uri('https://app.test/page');
        $request = (new ServerRequest())
            ->withUri($uri)
            ->withHeader(HttpHeader::REFERER, 'https://evil.example/hook');

        $factory = $this->makeFactory($request, $this->createStub(ViewInterface::class));
        $response = $factory->back(HttpStatusCode::FOUND, '/home');

        self::assertSame('/home', $response->getHeaderLine('Location'));
    }

    public function testViewMergesSharedContextAndSetsHtml(): void
    {
        $uri = new Uri('https://app.test/');
        $request = (new ServerRequest())
            ->withUri($uri)
            ->withAttribute(RequestAttribute::VIEW_PAYLOAD, ['shared' => 1, 'both' => 'from-shared']);

        $rendered = [];
        $view = $this->createStub(ViewInterface::class);
        $view->method('render')->willReturnCallback(function (string $name, array $data) use (&$rendered): string {
            $rendered = [$name, $data];

            return '<html/>';
        });

        $factory = $this->makeFactory($request, $view);
        $response = $factory->view('home.twig', ['both' => 'from-local', 'local' => true]);

        self::assertSame(HttpValue::HTML, $response->getHeaderLine(HttpHeader::CONTENT_TYPE));
        self::assertSame('<html/>', (string) $response->getBody());
        self::assertSame('home.twig', $rendered[0]);
        self::assertSame([
            'shared' => 1,
            'both' => 'from-local',
            'local' => true,
        ], $rendered[1]);
    }

    public function testViewIgnoresNonArrayViewContextAttribute(): void
    {
        $uri = new Uri('https://app.test/');
        $request = (new ServerRequest())
            ->withUri($uri)
            ->withAttribute(RequestAttribute::VIEW_PAYLOAD, 'broken');

        $mergedData = null;
        $view = $this->createStub(ViewInterface::class);
        $view->method('render')->willReturnCallback(function (string $name, array $data) use (&$mergedData): string {
            $mergedData = $data;

            return '';
        });

        $factory = $this->makeFactory($request, $view);
        $factory->view('x', ['only' => true]);

        self::assertSame(['only' => true], $mergedData);
    }

    private function makeFactory(ServerRequestInterface $request, ViewInterface $view): ResponseFactory
    {
        $container = new ArrayContainer([
            ServerRequestInterface::class => $request,
        ]);

        return new ResponseFactory($container, $view);
    }
}
