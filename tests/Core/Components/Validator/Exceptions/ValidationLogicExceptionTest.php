<?php declare(strict_types=1);

namespace Tests\Core\Components\Validator\Exceptions;

use Concept\Core\Components\Validator\Exceptions\ValidationLogicException;
use PHPUnit\Framework\TestCase;

final class ValidationLogicExceptionTest extends TestCase
{
    public function testBuildsHelpfulMessageWithClassName(): void
    {
        $e = new ValidationLogicException('App\\Http\\Form');

        self::assertStringContainsString('Validation has not been performed', $e->getMessage());
        self::assertStringContainsString('App\\Http\\Form', $e->getMessage());
    }
}
