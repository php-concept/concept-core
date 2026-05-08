<?php declare(strict_types=1);

namespace Tests\Core\Http\Protocol;

use Concept\Core\Http\Protocol\HttpStatusCode;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class HttpStatusCodeTest extends TestCase
{
    #[DataProvider('reasonPhraseProvider')]
    public function testReturnsKnownReasonPhrases(int $code, string $expected): void
    {
        self::assertSame($expected, HttpStatusCode::getReasonPhrase($code));
    }

    public function testReturnsGenericErrorForUnknownCode(): void
    {
        self::assertSame('Error', HttpStatusCode::getReasonPhrase(999));
    }

    /**
     * @return array<string, array{int, string}>
     */
    public static function reasonPhraseProvider(): array
    {
        return [
            'ok' => [HttpStatusCode::OK, 'OK'],
            'created' => [HttpStatusCode::CREATED, 'Created'],
            'no-content' => [HttpStatusCode::NO_CONTENT, 'No Content'],
            'moved-permanently' => [HttpStatusCode::MOVED_PERMANENTLY, 'Moved Permanently'],
            'found' => [HttpStatusCode::FOUND, 'Found'],
            'bad-request' => [HttpStatusCode::BAD_REQUEST, 'Bad Request'],
            'unauthorized' => [HttpStatusCode::UNAUTHORIZED, 'Unauthorized'],
            'forbidden' => [HttpStatusCode::FORBIDDEN, 'Forbidden'],
            'not-found' => [HttpStatusCode::NOT_FOUND, 'Not Found'],
            'method-not-allowed' => [HttpStatusCode::METHOD_NOT_ALLOWED, 'Method Not Allowed'],
            'page-expired' => [HttpStatusCode::PAGE_EXPIRED, 'Page Expired'],
            'unprocessable-entity' => [HttpStatusCode::UNPROCESSABLE_ENTITY, 'Unprocessable Entity'],
            'internal-server-error' => [HttpStatusCode::INTERNAL_SERVER_ERROR, 'Internal Server Error'],
            'not-implemented' => [HttpStatusCode::NOT_IMPLEMENTED, 'Not Implemented'],
            'service-unavailable' => [HttpStatusCode::SERVICE_UNAVAILABLE, 'Service Unavailable'],
        ];
    }
}
