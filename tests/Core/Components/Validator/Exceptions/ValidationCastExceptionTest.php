<?php declare(strict_types=1);

namespace Tests\Core\Components\Validator\Exceptions;

use Concept\Core\Components\Validator\Exceptions\ValidationCastException;
use PHPUnit\Framework\TestCase;

final class ValidationCastExceptionTest extends TestCase
{
    public function testExtendsBaseExceptionBehavior(): void
    {
        $prev = new \RuntimeException('root');
        $e = new ValidationCastException('Cast failed', 77, $prev);

        self::assertSame('Cast failed', $e->getMessage());
        self::assertSame(77, $e->getCode());
        self::assertSame($prev, $e->getPrevious());
    }
}
