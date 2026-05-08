<?php declare(strict_types=1);

namespace Concept\Core\Components\Validator\Exceptions;

use Concept\Core\Http\Protocol\HttpStatusCode;
use Exception;

class ValidationException extends Exception
{
    private const string DEFAULT_MESSAGE = 'Validation failed';
    private const int DEFAULT_CODE = HttpStatusCode::UNPROCESSABLE_ENTITY;

    /**
     * @param array<string, string[]> $errors
     * @param array<string, mixed> $oldData
     */
    public function __construct(private readonly array $errors, private readonly array $oldData)
    {
        parent::__construct(self::DEFAULT_MESSAGE, self::DEFAULT_CODE);
    }

    /**
     * @return string[][]
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    /**
     * @return mixed[]
     */
    public function getOldData(): array
    {
        return $this->oldData;
    }
}