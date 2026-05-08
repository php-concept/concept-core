<?php declare(strict_types=1);

namespace Concept\Core\Components\Validator;

use Concept\Core\Components\Validator\Adapters\RakitRuleAdapter;
use Concept\Core\Components\Validator\Adapters\RakitValidationAdapter;
use Concept\Core\Components\Validator\Contracts\RuleInterface;
use Concept\Core\Components\Validator\Contracts\ValidationInterface;
use Concept\Core\Components\Validator\Contracts\ValidatorInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\ContainerInterface;
use Psr\Container\NotFoundExceptionInterface;
use Rakit\Validation\RuleQuashException;
use Rakit\Validation\Validator as RakitValidator;

class Validator implements ValidatorInterface
{
    public function __construct(
        private readonly ContainerInterface $container,
        private readonly RakitValidator $rakitValidator
    ) {}

    public function make(array $data, array $rulesConfig): ValidationInterface
    {
        $rakitValidation = $this->rakitValidator->make($data, $rulesConfig);

        return new RakitValidationAdapter($rakitValidation);
    }

    /**
     * @param array<string, class-string> $rules
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     * @throws RuleQuashException
     */
    public function addRules(array $rules): void
    {
        foreach ($rules as $name => $class) {
            /** @var RuleInterface $customRule */
            $customRule = $this->container->get($class);
            $rakitRule = new RakitRuleAdapter($customRule);
            $this->rakitValidator->addValidator($name, $rakitRule);
        }
    }
}