<?php declare(strict_types=1);

namespace Tests\Core;

use Concept\Core\Components\Validator\Validator;
use PHPUnit\Framework\TestCase;
use Rakit\Validation\Validator as RakitValidator;
use Tests\Fixtures\Core\AlwaysTrueRule;
use Tests\Fixtures\Core\ArrayContainer;

final class ValidatorTest extends TestCase
{
    public function testMakeRunsBuiltInRules(): void
    {
        $validator = new Validator(
            new ArrayContainer([]),
            new RakitValidator()
        );

        $invalid = $validator->make([], ['email' => 'required|email']);
        $invalid->validate();
        self::assertFalse($invalid->isValid());

        $valid = $validator->make(['email' => 'user@example.com'], ['email' => 'required|email']);
        $valid->validate();
        self::assertTrue($valid->isValid());
    }

    public function testAddRulesRegistersCustomValidator(): void
    {
        $container = new ArrayContainer([
            AlwaysTrueRule::class => new AlwaysTrueRule(),
        ]);

        $validator = new Validator($container, new RakitValidator());
        $validator->addRules(['always_ok' => AlwaysTrueRule::class]);

        $validation = $validator->make(['field' => 'any'], ['field' => 'always_ok']);
        $validation->validate();

        self::assertTrue($validation->isValid());
    }
}
