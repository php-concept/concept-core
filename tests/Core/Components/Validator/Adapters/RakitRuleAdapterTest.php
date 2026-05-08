<?php declare(strict_types=1);

namespace Tests\Core\Components\Validator\Adapters;

use Concept\Core\Components\Validator\Adapters\RakitRuleAdapter;
use Concept\Core\Components\Validator\Contracts\RuleInterface;
use PHPUnit\Framework\TestCase;
use Rakit\Validation\MissingRequiredParameterException;

final class RakitRuleAdapterTest extends TestCase
{
    public function testCheckPassesValueAndParametersToCustomRule(): void
    {
        $rule = new TrackingRule();
        $adapter = new RakitRuleAdapter($rule);
        $adapter->setParameters(['min' => 3]);

        $result = $adapter->check('abcd');

        self::assertTrue($result);
        self::assertSame(['min' => 3], $rule->receivedParams);
        self::assertSame('abcd', $rule->receivedValue);
        self::assertSame('Length must be at least :min', $adapter->getMessage());
    }

    public function testCheckThrowsWhenRequiredRuleParameterMissing(): void
    {
        $adapter = new RakitRuleAdapter(new TrackingRule());

        $this->expectException(MissingRequiredParameterException::class);
        $adapter->check('abc');
    }
}

final class TrackingRule implements RuleInterface
{
    public mixed $receivedValue = null;

    /** @var array<mixed> */
    public array $receivedParams = [];

    public function passes($value): bool
    {
        $this->receivedValue = $value;
        $min = (int) ($this->receivedParams['min'] ?? 0);

        return is_string($value) && strlen($value) >= $min;
    }

    public function getMessage(): string
    {
        return 'Length must be at least :min';
    }

    public function getRequired(): array
    {
        return ['min'];
    }

    public function getFillable(): array
    {
        return ['min'];
    }

    public function setParameters(array $params): void
    {
        $this->receivedParams = $params;
    }
}
