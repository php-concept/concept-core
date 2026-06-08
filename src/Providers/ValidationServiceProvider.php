<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Components\Locale\Contracts\LocaleResolverInterface;
use Concept\Core\Foundation\PathManager;
use Concept\Core\Telemetry\TelemetryEvent;
use Concept\Core\Telemetry\TelemetryTrait;
use Concept\Core\Components\Validator\Contracts\ValidatorInterface;
use Concept\Core\Components\Validator\ValidationTranslationsLoader;
use Concept\Core\Components\Validator\Validator;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Rakit\Validation\Validator as RakitValidator;

class ValidationServiceProvider extends AbstractServiceProvider
{
    use TelemetryTrait;

    public function provides(string $id): bool
    {
        $services = [
            RakitValidator::class,
            ValidatorInterface::class,
            ValidationTranslationsLoader::class,
        ];

        return in_array($id, $services);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(RakitValidator::class, function () {
            return new RakitValidator();
        })->setShared(true);

        $container->add(ValidationTranslationsLoader::class, function () use ($container) {
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, ValidationTranslationsLoader::class);

            /** @var LocaleResolverInterface $localeResolver */
            $localeResolver = $container->get(LocaleResolverInterface::class);
            /** @var PathManager $pathManager */
            $pathManager = $container->get(PathManager::class);
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            return new ValidationTranslationsLoader($localeResolver, $pathManager, $config);
        })->setShared(true);

        $container->add(ValidatorInterface::class, function () use ($container) {
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, ValidatorInterface::class);

            /** @var RakitValidator $rakitValidator */
            $rakitValidator = $container->get(RakitValidator::class);
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            /** @var array<string, class-string> $customRules */
            $customRules = $config->get(ConfigKey::VALIDATOR_RULES, []);

            $validator = new Validator($container, $rakitValidator);
            $validator->addRules($customRules);

            return $validator;
        })->setShared(true);
    }
}
