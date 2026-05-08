<?php declare(strict_types=1);

namespace Concept\Core\Components\Validator\Contracts;

interface ValidatorInterface
{
    /**
     * @param array<mixed> $rules
     * @return void
     */
    public function addRules(array $rules): void;

    /**
     * @param array<mixed> $data
     * @param array<mixed> $rulesConfig
     * @return ValidationInterface
     */
    public function make(array $data, array $rulesConfig): ValidationInterface;
}