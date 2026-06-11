<?php declare(strict_types=1);

namespace Concept\Core\Components\DataMasker;

use Concept\Core\Components\DataMasker\Contracts\DataMaskerRuleInterface;

class RegexDataMaskerRule implements DataMaskerRuleInterface
{
    /** @var array<string, string> */
    private array $patterns = [];

    /** @var array<string>  */
    private array $keyPatterns = [];

    /**
     * @param array<string, string> $patterns
     * @param array<string, string> $keyPatterns
     */
    public function __construct(array $patterns = [], array $keyPatterns = [])
    {
        $this->patterns = $patterns;
        $this->keyPatterns = $keyPatterns;
    }

    public function isSensitiveKey(string $key): bool
    {
        return (bool) preg_filter($this->keyPatterns, $key, $key);
    }

    public function apply(string $value): string
    {
        $masked = preg_replace(array_keys($this->patterns), array_values($this->patterns), $value);

        return $masked ?? $value;
    }
}