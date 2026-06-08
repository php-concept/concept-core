<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Components\DataMasker\Contracts\DataMaskerInterface;
use Concept\Core\Components\DataMasker\Contracts\DataMaskerRuleInterface;
use Concept\Core\Components\DataMasker\DataMasker;
use Concept\Core\Components\DataMasker\RegexMaskerRule;
use Concept\Core\Components\Telemetry\TelemetryEvent;
use Concept\Core\Components\Telemetry\TelemetryTrait;
use League\Container\ServiceProvider\AbstractServiceProvider;

class DataMaskerServiceProvider extends AbstractServiceProvider
{
    use TelemetryTrait;

    public function provides(string $id): bool
    {
        return $id === DataMaskerInterface::class;
    }

    public function register(): void
    {
        $container = $this->getContainer();
        $container->add(DataMaskerInterface::class, function () use ($container) {
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, DataMaskerInterface::class);

            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            $masker = new DataMasker();

            /** @var array<string, string> $patterns */
            $patterns = $config->get(ConfigKey::MASKING_PATTERNS, []);
            /** @var array<string, string> $keyPatterns */
            $keyPatterns = $config->get(ConfigKey::MASKING_KEY_PATTERNS, []);
            $masker->addRule(new RegexMaskerRule($patterns, $keyPatterns));

            /** @var array<string, string> $rules */
            $rules = $config->get(ConfigKey::MASKING_RULES, []);
            foreach ($rules as $ruleClass) {
                /** @var DataMaskerRuleInterface $ruleClassInstance */
                $ruleClassInstance = $container->get($ruleClass);
                $masker->addRule($ruleClassInstance);
            }

            return $masker;
        })->setShared(true);
    }
}