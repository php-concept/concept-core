<?php declare(strict_types=1);

namespace Concept\Core\Providers\Support;

use Concept\Core\Services\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Services\Locale\ConfigLocaleResolver;
use Concept\Core\Services\Locale\Contracts\LocaleResolverInterface;
use Concept\Core\Telemetry\TelemetryEvent;
use Concept\Core\Telemetry\TelemetryTrait;
use League\Container\ServiceProvider\AbstractServiceProvider;

class LocaleServiceProvider extends AbstractServiceProvider
{
    use TelemetryTrait;

    public function provides(string $id): bool
    {
        return $id === LocaleResolverInterface::class;
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(LocaleResolverInterface::class, function () use ($container) {
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, LocaleResolverInterface::class);

            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);
            $resolverClass = $config->get(ConfigKey::APP_LOCALE_RESOLVER);

            if (!is_string($resolverClass) || $resolverClass === '') {
                return $container->get(ConfigLocaleResolver::class);
            }

            /** @var LocaleResolverInterface $resolver */
            $resolver = $container->get($resolverClass);

            return $resolver;
        })->setShared(true);
    }
}
