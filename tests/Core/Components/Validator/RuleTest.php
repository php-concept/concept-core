<?php declare(strict_types=1);

namespace Tests\Core\Components\Validator;

use Concept\Core\Components\Validator\Rule;
use PHPUnit\Framework\TestCase;

final class RuleTest extends TestCase
{
    public function testBaseRuleExposesConfigurableState(): void
    {
        $rule = new TestRule();
        $rule->setParameters(['min' => 3, 'max' => 8]);

        self::assertSame('The :attribute is invalid', $rule->getMessage());
        self::assertSame(['min'], $rule->getRequired());
        self::assertSame(['min', 'max'], $rule->getFillable());
        self::assertTrue($rule->passes('abcdef'));
    }

    public function testBaseRuleCanUseDefaultParameterValues(): void
    {
        $rule = new TestRule();
        $rule->setParameters(['min' => 2]);

        self::assertSame(2, $rule->readParam('min'));
        self::assertSame(10, $rule->readParam('max', 10));
    }
}

final class TestRule extends Rule
{
    protected array $fillable = ['min', 'max'];

    protected array $required = ['min'];

    public function passes($value): bool
    {
        $min = (int) $this->parameter('min', 0);
        $max = (int) $this->parameter('max', PHP_INT_MAX);

        return is_string($value) && strlen($value) >= $min && strlen($value) <= $max;
    }

    public function readParam(string $key, mixed $default = null): mixed
    {
        return $this->parameter($key, $default);
    }
}
