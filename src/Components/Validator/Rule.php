<?php declare(strict_types=1);

namespace Concept\Core\Components\Validator;

use Concept\Core\Components\Validator\Contracts\RuleInterface;

abstract class Rule implements RuleInterface
{
    /** @var string */
    protected string $message = 'The :attribute is invalid';

    /** @var array<int, string> */
    protected array $fillable = [];

    /** @var array<int, string> */
    protected array $required = [];

    /** @var array<string, mixed> */
    protected array $params = [];

    /**
     * @param mixed $value
     * @return bool
     */
    abstract public function passes($value): bool;

    /**
     * @param string $key
     * @param mixed $default
     * @return mixed
     */
    protected function parameter(string $key, $default = null): mixed
    {
        return $this->params[$key] ?? $default;
    }

    /**
     * @param array<mixed> $params
     * @return void
     */
    public function setParameters(array $params): void
    {
        $this->params = $params;
    }

    public function getRequired(): array
    {
        return $this->required;
    }

    public function getFillable(): array
    {
        return $this->fillable;
    }

    public function getMessage(): string
    {
        return $this->message;
    }
}