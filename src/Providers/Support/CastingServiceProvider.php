<?php declare(strict_types=1);

namespace Concept\Core\Providers\Support;

use Concept\Core\Services\Caster\Caster;
use Concept\Core\Services\Caster\Contracts\CasterInterface;
use Concept\Core\Services\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\PathManager;
use Concept\Core\Telemetry\TelemetryEvent;
use Concept\Core\Telemetry\TelemetryTrait;
use League\Container\ServiceProvider\AbstractServiceProvider;

class CastingServiceProvider extends AbstractServiceProvider
{
    use TelemetryTrait;
    public function provides(string $id): bool
    {
        return $id == CasterInterface::class;
    }

    public function register(): void
    {
        $container = $this->getContainer();
        $container->add(CasterInterface::class, function () use ($container) {
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, CasterInterface::class);

            /** @var PathManager $pathManager */
            $pathManager = $container->get(PathManager::class);
            /** @var ConfigInterface $config */
            $config = $this->getContainer()->get(ConfigInterface::class);

            return new Caster($pathManager, $config);
        })->setShared(true);
    }
}