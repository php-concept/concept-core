<?php declare(strict_types=1);

namespace Tests\Core;

use Concept\Core\Http\Exceptions\Security\CsrfException;
use Concept\Core\Http\Protocol\HttpStatusCode;
use PHPUnit\Framework\TestCase;

final class CsrfExceptionTest extends TestCase
{
    public function testDefaultMessageAndCode(): void
    {
        $e = new CsrfException();

        self::assertSame('CSRF token mismatch', $e->getMessage());
        self::assertSame(HttpStatusCode::PAGE_EXPIRED, $e->getCode());
    }

    public function testCustomMessagePreservesCode(): void
    {
        $e = new CsrfException('Nope');

        self::assertSame('Nope', $e->getMessage());
        self::assertSame(HttpStatusCode::PAGE_EXPIRED, $e->getCode());
    }
}
