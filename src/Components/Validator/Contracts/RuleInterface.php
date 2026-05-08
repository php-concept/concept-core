<?php declare(strict_types=1);

namespace Concept\Core\Components\Validator\Contracts;

interface RuleInterface
{
    /**
     * @param mixed $value
     * @return bool
     */
    public function passes($value): bool;

    public function getMessage(): string;

    /**
     * @return array<string>
     */
    public function getRequired(): array;

    /**
     * @return array<string>
     */
    public function getFillable(): array;

    /**
     * @param array<mixed> $params
     * @return void
     */
    public function setParameters(array $params): void;
}