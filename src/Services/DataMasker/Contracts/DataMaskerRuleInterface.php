<?php declare(strict_types=1);

namespace Concept\Core\Services\DataMasker\Contracts;

interface DataMaskerRuleInterface
{
    public function isSensitiveKey(string $key): bool;

    public function apply(string $value): string;
}