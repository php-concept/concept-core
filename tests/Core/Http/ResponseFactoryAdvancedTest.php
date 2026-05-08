<?php declare(strict_types=1);

namespace Tests\Core\Http;

use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Http\Protocol\HttpStatusCode;
use Concept\Core\Http\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Fixtures\Core\ArrayContainer;
use Twig\Error\LoaderError;

final class ResponseFactoryAdvancedTest extends TestCase
{
    /**
     * Verifies that view() method correctly sets custom HTTP status codes.
     */
    public function testViewSetsCustomStatusCode(): void
    {
        $view = $this->createStub(ViewInterface::class);
        $view->method('render')->willReturn('Error Page');
        
        $factory = $this->makeFactory($this->createStub(ServerRequestInterface::class), $view);
        $response = $factory->view('errors/404', [], HttpStatusCode::NOT_FOUND);
        
        self::assertSame(HttpStatusCode::NOT_FOUND, $response->getStatusCode());
        self::assertSame('Error Page', (string) $response->getBody());
    }

    /**
     * Verifies that view() propagates Twig exceptions when template is missing.
     */
    public function testViewPropagatesTwigLoaderError(): void
    {
        $view = $this->createStub(ViewInterface::class);
        $view->method('render')->willThrowException(new LoaderError('Template "missing.twig" not found.'));
        
        $factory = $this->makeFactory($this->createStub(ServerRequestInterface::class), $view);
        
        $this->expectException(LoaderError::class);
        $this->expectExceptionMessage('Template "missing.twig" not found.');
        
        $factory->view('missing');
    }

    /**
     * Verifies that json() method correctly sets custom HTTP status codes.
     */
    public function testJsonSetsCustomStatusCode(): void
    {
        $factory = $this->makeFactory($this->createStub(ServerRequestInterface::class), $this->createStub(ViewInterface::class));
        $response = $factory->json(['id' => 123], HttpStatusCode::CREATED);
        
        self::assertSame(HttpStatusCode::CREATED, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"id":123}', (string) $response->getBody());
    }

    private function makeFactory(ServerRequestInterface $request, ViewInterface $view): ResponseFactory
    {
        $container = new ArrayContainer([
            ServerRequestInterface::class => $request,
        ]);

        return new ResponseFactory($container, $view);
    }
}
