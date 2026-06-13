<?php declare(strict_types=1);

namespace Concept\Core\Providers\Support;

use Concept\Core\Services\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Services\Locale\ConfigLocaleResolver;
use Concept\Core\Services\Locale\Contracts\LocaleResolverInterface;
use Concept\Core\Services\Telemetry\TelemetryEvent;
use Concept\Core\Services\Telemetry\TelemetryTrait;
use League\Container\ServiceProvider\AbstractServiceProvider;

class LocaleServiceProvider extends AbstractServiceProvider
{
    use TelemetryTrait;

    public function provides(string $id): bool
    {
        $services = [
            ConfigLocaleResolver::class,
            LocaleResolverInterface::class,
        ];

        return in_array($id, $services);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(ConfigLocaleResolver::class, function () use ($container) {
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, ConfigLocaleResolver::class);

            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            return new ConfigLocaleResolver($config);
        })->setShared(true);

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
