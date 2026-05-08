<?php declare(strict_types=1);

namespace Tests\Core\Components\Validator\Exceptions;

use Concept\Core\Components\Validator\Exceptions\ValidationException;
use Concept\Core\Http\Protocol\HttpStatusCode;
use PHPUnit\Framework\TestCase;

final class ValidationExceptionTest extends TestCase
{
    public function testStoresErrorsOldDataAndDefaultMeta(): void
    {
        $errors = ['email' => ['required', 'email']];
        $oldData = ['email' => 'bad-value'];

        $e = new ValidationException($errors, $oldData);

        self::assertSame('Validation failed', $e->getMessage());
        self::assertSame(HttpStatusCode::UNPROCESSABLE_ENTITY, $e->getCode());
        self::assertSame($errors, $e->getErrors());
        self::assertSame($oldData, $e->getOldData());
    }
}
