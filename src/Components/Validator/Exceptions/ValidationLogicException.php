<?php declare(strict_types=1);

namespace Concept\Core\Components\Validator\Exceptions;

use LogicException;

class ValidationLogicException extends LogicException
{
    private const string MSG_VALIDATION_NOT_READY =
        'Validation has not been performed on class %s. You must call validate() before accessing validated data.';

    public function __construct(string $className)
    {
        parent::__construct(sprintf(self::MSG_VALIDATION_NOT_READY, $className));
    }
}