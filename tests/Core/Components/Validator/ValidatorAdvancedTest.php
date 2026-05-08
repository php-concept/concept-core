<?php declare(strict_types=1);

namespace Tests\Core\Components\Validator;

use Concept\Core\Components\Validator\Rule;
use Concept\Core\Components\Validator\Validator;
use PHPUnit\Framework\TestCase;
use Rakit\Validation\Validator as RakitValidator;
use Tests\Fixtures\Core\ArrayContainer;

/**
 * Advanced tests for the Validator component focusing on custom rules and parameter passing.
 */
class ParametrizedRule extends Rule
{
    protected array $fillable = ['min', 'max'];
    protected array $required = ['min', 'max'];

    public function passes($value): bool
    {
        $min = (int) $this->parameter('min');
        $max = (int) $this->parameter('max');

        return $value >= $min && $value <= $max;
    }
}

final class ValidatorAdvancedTest extends TestCase
{
    /**
     * Verifies that parameters defined in the validation string (e.g. "rule:10,20")
     * are correctly passed to the custom rule object via the adapter.
     */
    public function testCustomRuleReceivesParametersFromValidationString(): void
    {
        $container = new ArrayContainer([
            ParametrizedRule::class => new ParametrizedRule(),
        ]);

        $validator = new Validator($container, new RakitValidator());
        $validator->addRules(['range' => ParametrizedRule::class]);

        // Test valid value within range 10-20
        $valid = $validator->make(['age' => 15], ['age' => 'range:10,20']);
        $valid->validate();
        self::assertTrue($valid->isValid(), 'Value 15 should be valid for range 10-20');

        // Test invalid value outside range
        $invalid = $validator->make(['age' => 5], ['age' => 'range:10,20']);
        $invalid->validate();
        self::assertFalse($invalid->isValid(), 'Value 5 should be invalid for range 10-20');
    }

    /**
     * Verifies that if a custom rule is missing required parameters, 
     * the underlying Rakit validation throws an exception.
     */
    public function testThrowsExceptionWhenRequiredParametersAreMissing(): void
    {
        $container = new ArrayContainer([
            ParametrizedRule::class => new ParametrizedRule(),
        ]);

        $validator = new Validator($container, new RakitValidator());
        $validator->addRules(['range' => ParametrizedRule::class]);

        $validation = $validator->make(['age' => 15], ['age' => 'range:10']); // Missing 'max'
        
        $this->expectException(\Rakit\Validation\MissingRequiredParameterException::class);
        $validation->validate();
    }
}
