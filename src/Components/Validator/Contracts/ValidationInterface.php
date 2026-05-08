<?php declare(strict_types=1);

namespace Concept\Core\Components\Validator\Contracts;

interface ValidationInterface
{
    public function validate(): void;

    public function isValid(): bool;

    /**
     * @return array<string, string[]>
     */
    public function getErrors(): array;

    /**
     * @return array<string, mixed>
     */
    public function getValidData(): array;

     /**
     * @param array<string, string> $aliases
     */
    public function setAliases(array $aliases): void;
}