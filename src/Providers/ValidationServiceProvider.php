<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Validator\Contracts\ValidatorInterface;
use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Validator\Validator;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Rakit\Validation\Validator as RakitValidator;

class ValidationServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        $services = [
            RakitValidator::class,
            ValidatorInterface::class,
        ];

        return in_array($id, $services);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(RakitValidator::class, function () {
            return new RakitValidator();
        })->setShared(true);

        $container->add(ValidatorInterface::class, function () use ($container) {
            /** @var RakitValidator $rakitValidator */
            $rakitValidator = $container->get(RakitValidator::class);
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            /** @var array<string, class-string> $customRules */
            $customRules = $config->get('validator.rules', []);

            $validator = new Validator($container, $rakitValidator);
            $validator->addRules($customRules);

            return $validator;
        })->setShared(true);
    }
}