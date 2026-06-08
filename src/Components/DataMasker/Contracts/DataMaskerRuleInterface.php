<?php declare(strict_types=1);

namespace Concept\Core\Components\DataMasker\Contracts;

interface DataMaskerRuleInterface
{
    public function isSensitiveKey(string $key): bool;

    public function apply(string $value): string;
}