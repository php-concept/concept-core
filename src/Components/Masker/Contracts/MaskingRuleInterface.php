<?php declare(strict_types=1);

namespace Concept\Core\Components\Masker\Contracts;

interface MaskingRuleInterface
{
    /**
     * @param array<string, string> $customPatterns
     * @param array<string, string> $customKeyPatterns
     */
    public function addPatterns(array $customPatterns = [], array $customKeyPatterns = []): void;

    public function clearPatterns(): void;

    public function isSensitiveKey(string $key): bool;

    public function apply(string $value): string;
}