<?php declare(strict_types=1);

namespace Concept\Core\Components\Masker;

use Concept\Core\Components\Masker\Contracts\MaskingRuleInterface;

class RegexMasker implements MaskingRuleInterface
{
    /** @var array<string, string> */
    private array $patterns = [
        // 1. Email
        '/[a-z0-9_\-\+\.]+@[a-z0-9\-]+\.[a-z]{2,}/i' => '***@***.***',

        // 2. Cards
        '/\d{4}-\d{4}-\d{4}-\d{4}/' => '****-****-****-****',

        // 3. Passwords, tokens, CSRF...
        '/(password|passwd|pwd|repeat_password|password_confirmation|token|_csrf_token|csrf_token|api_key|secret|authorization)[:=]+([^\s,;]+)/i' => '$1=*****',
    ];

    /** @var array<string>  */
    private array $keyPatterns = [
        '/.*password.*/i',
        '/.*token.*/i',
        '/.*_csrf_token.*/i',
        '/.*secret.*/i',
        '/api_key/i',
        '/authorization/i'
    ];

    /**
     * @param array<string, string> $customPatterns
     * @param array<string, string> $customKeyPatterns
     */
    public function __construct(array $customPatterns = [], array $customKeyPatterns = [])
    {
        $this->patterns = array_merge($this->patterns, $customPatterns);
        $this->keyPatterns = array_merge($this->keyPatterns, $customKeyPatterns);
    }

    /**
     * @param array<string, string> $customPatterns
     * @param array<string, string> $customKeyPatterns
     */
    public function addPatterns(array $customPatterns = [], array $customKeyPatterns = []): void
    {
        $this->patterns = array_merge($this->patterns, $customPatterns);
        $this->keyPatterns = array_merge($this->keyPatterns, $customKeyPatterns);
    }

    public function clearPatterns(): void
    {
        $this->patterns = [];
        $this->keyPatterns = [];
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