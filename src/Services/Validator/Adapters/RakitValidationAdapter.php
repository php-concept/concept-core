<?php declare(strict_types=1);

namespace Concept\Core\Services\Validator\Adapters;

use Concept\Core\Services\Validator\Contracts\ValidationInterface;
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

    public function setMessages(array $messages): void
    {
        $this->rakitValidation->setMessages($messages);
    }

    public function setTranslations(array $translations): void
    {
        $this->rakitValidation->setTranslations($translations);
    }
}