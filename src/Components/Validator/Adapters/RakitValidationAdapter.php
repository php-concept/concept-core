<?php declare(strict_types=1);

namespace Concept\Core\Components\Validator\Adapters;

use Concept\Core\Components\Validator\Contracts\ValidationInterface;
use Rakit\Validation\Validation as RakitValidation;

class RakitValidationAdapter implements ValidationInterface
{
    public function __construct(private readonly RakitValidation $rakitValidation) {}

    public function validate(): void
    {
        $this->rakitValidation->validate();
    }

    public function isValid(): bool
    {
        return !$this->rakitValidation->fails();
    }

    public function getValidData(): array
    {
        return $this->rakitValidation->getValidData();
    }

    public function getErrors(): array
    {
        return $this->rakitValidation->errors()->toArray();
    }

    public function setAliases(array $aliases): void
    {
        $this->rakitValidation->setAliases($aliases);
    }
}