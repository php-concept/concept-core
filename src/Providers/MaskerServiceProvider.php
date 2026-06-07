<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Masker\Contracts\MaskerInterface;
use Concept\Core\Components\Masker\DataMasker;
use Concept\Core\Components\Masker\RegexMasker;
use Concept\Core\Components\Telemetry\TelemetryEvent;
use Concept\Core\Components\Telemetry\TelemetryTrait;
use League\Container\ServiceProvider\AbstractServiceProvider;

class MaskerServiceProvider extends AbstractServiceProvider
{
    use TelemetryTrait;

    public function provides(string $id): bool
    {
        return $id === MaskerInterface::class;
    }

    public function register(): void
    {
        $this->getContainer()->add(MaskerInterface::class, function () {
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, MaskerInterface::class);

            $masker = new DataMasker();
            $masker->addRule(new RegexMasker());

            return $masker;
        })->setShared(true);
    }
}