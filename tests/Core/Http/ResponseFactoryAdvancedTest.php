<?php declare(strict_types=1);

namespace Tests\Core\Http;

use Concept\Core\Http\Protocol\HttpStatusCode;
use Concept\Core\Http\ResponseFactory;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;
use Tests\Fixtures\Core\ArrayContainer;

final class ResponseFactoryAdvancedTest extends TestCase
{
    /**
     * Verifies that json() method correctly sets custom HTTP status codes.
     */
    public function testJsonSetsCustomStatusCode(): void
    {
        $factory = $this->makeFactory($this->createStub(ServerRequestInterface::class));
        $response = $factory->json(['id' => 123], HttpStatusCode::CREATED);
        
        self::assertSame(HttpStatusCode::CREATED, $response->getStatusCode());
        self::assertJsonStringEqualsJsonString('{"id":123}', (string) $response->getBody());
    }

    private function makeFactory(ServerRequestInterface $request): ResponseFactory
    {
        $container = new ArrayContainer([
            ServerRequestInterface::class => $request,
        ]);

        return new ResponseFactory($container);
    }
}
