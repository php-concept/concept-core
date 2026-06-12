<?php declare(strict_types=1);

namespace Concept\Core\Services\DataMasker;

use Concept\Core\Services\DataMasker\Contracts\DataMaskerInterface;
use Concept\Core\Services\DataMasker\Contracts\DataMaskerRuleInterface;

class DataMasker implements DataMaskerInterface
{
    public const MASK_CHARS = '***';

    /** @var DataMaskerRuleInterface[] */
    private array $rules = [];

    /**
     * @param DataMaskerRuleInterface $rule
     */
    public function addRule(DataMaskerRuleInterface $rule): void
    {
        $this->rules[] = $rule;
    }

    public function clearRules(): void
    {
        $this->rules = [];
    }

    /**
     * Masks sensitive data in mixed input.
     *
     * @param mixed $data
     * @return mixed
     */
    public function mask(mixed $data): mixed
    {
        if (empty($this->rules)) {
            return $data;
        }

        if (is_array($data)) {
            /** @var array<mixed> $cloned */
            $cloned = $this->deepClone($data);
            return $this->maskArray($cloned);
        }

        if (is_object($data)) {
            /** @var object $cloned */
            $cloned = $this->deepClone($data);
            return $this->maskObject($cloned);
        }

        if (is_string($data)) {
            return $this->maskString($data);
        }

        return $data;
    }

    /**
     * Masks sensitive data in mixed input.
     *
     * @param mixed $data
     * @return mixed
     */
    public function maskRecursive(mixed $data): mixed
    {
        if (empty($this->rules)) {
            return $data;
        }

        if (is_array($data)) {
            return $this->maskArray($data);
        }

        if (is_object($data)) {
            return $this->maskObject($data);
        }

        if (is_string($data)) {
            return $this->maskString($data);
        }

        return $data;
    }

    /**
     * Recursively masks sensitive keys in an array.
     *
     * @param array<mixed> $data
     * @return array<mixed>
     */
    private function maskArray(array $data): array
    {
        foreach ($data as $key => &$value) {
            if ($this->isSensitiveKey((string)$key)) {
                $value = self::MASK_CHARS;
                continue;
            }

            $value = $this->maskRecursive($value);
        }

        return $data;
    }

    /**
     * Masks properties of an object using reflection.
     *
     * @param object $data
     * @return object
     */
    private function maskObject(object $data): object
    {
        $reflection = new \ReflectionObject($data);

        foreach ($reflection->getProperties() as $property) {
            $property->setAccessible(true);
            if ($this->isSensitiveKey($property->getName())) {
                $property->setValue($data, self::MASK_CHARS);
                continue;
            }

            $property->setValue($data, $this->maskRecursive($property->getValue($data)));
        }

        return $data;
    }

    /**
     * Applies masking rules to a string.
     *
     * @param string $data
     * @return string
     */
    private function maskString(string $data): string
    {
        foreach ($this->rules as $rule) {
            $data = $rule->apply($data);
        }

        return $data;
    }

    /**
     * Checks if a key should be masked according to current rules.
     *
     * @param string $key
     * @return bool
     */
    private function isSensitiveKey(string $key): bool
    {
        foreach ($this->rules as $rule) {
            if ($rule->isSensitiveKey($key)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @param mixed $value
     * @return mixed
     */
    function deepClone(mixed $value): mixed
    {
        if (is_array($value)) {
            return array_map([$this, 'deepClone'], $value);
        }

        if (is_object($value)) {
            return clone $value;
        }

        return $value;
    }
}