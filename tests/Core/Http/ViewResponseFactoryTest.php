<?php declare(strict_types=1);

namespace Tests\Core\Http;

use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Components\View\ViewResponseFactory;
use Concept\Core\Http\Protocol\HttpHeader;
use Concept\Core\Http\Protocol\HttpStatusCode;
use Concept\Core\Http\Protocol\HttpValue;
use Concept\Core\Http\RequestAttribute;
use Concept\Core\Http\ResponseFactory;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;
use Twig\Error\LoaderError;

final class ViewResponseFactoryTest extends TestCase
{
    public function testCreateMergesSharedContextAndSetsHtml(): void
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

        $container = new \Tests\Fixtures\Core\ArrayContainer([]);
        $responseFactory = new ResponseFactory($container);
        $factory = new ViewResponseFactory($responseFactory, $view);
        
        $response = $factory->create($request, 'home.twig', ['both' => 'from-local', 'local' => true]);

        self::assertSame(HttpStatusCode::OK, $response->getStatusCode());
        self::assertSame(HttpValue::HTML, $response->getHeaderLine(HttpHeader::CONTENT_TYPE));
        self::assertSame('<html/>', (string) $response->getBody());
        self::assertSame('home.twig', $rendered[0]);
        self::assertSame([
            'shared' => 1,
            'both' => 'from-local',
            'local' => true,
        ], $rendered[1]);
    }

    public function testCreateIgnoresNonArrayViewContextAttribute(): void
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

        $container = new \Tests\Fixtures\Core\ArrayContainer([]);
        $responseFactory = new ResponseFactory($container);
        $factory = new ViewResponseFactory($responseFactory, $view);
        
        $factory->create($request, 'x', ['only' => true]);

        self::assertSame(['only' => true], $mergedData);
    }

    public function testCreateSetsCustomStatusCode(): void
    {
        $view = $this->createStub(ViewInterface::class);
        $view->method('render')->willReturn('Error Page');
        
        $container = new \Tests\Fixtures\Core\ArrayContainer([]);
        $responseFactory = new ResponseFactory($container);
        $factory = new ViewResponseFactory($responseFactory, $view);
        
        $response = $factory->create(new ServerRequest(), 'errors/404', [], HttpStatusCode::NOT_FOUND);
        
        self::assertSame(HttpStatusCode::NOT_FOUND, $response->getStatusCode());
        self::assertSame('Error Page', (string) $response->getBody());
    }

    public function testCreatePropagatesTwigLoaderError(): void
    {
        $view = $this->createStub(ViewInterface::class);
        $view->method('render')->willThrowException(new LoaderError('Template "missing.twig" not found.'));
        
        $container = new \Tests\Fixtures\Core\ArrayContainer([]);
        $responseFactory = new ResponseFactory($container);
        $factory = new ViewResponseFactory($responseFactory, $view);
        
        $this->expectException(LoaderError::class);
        $this->expectExceptionMessage('Template "missing.twig" not found.');
        
        $factory->create(new ServerRequest(), 'missing');
    }
}
