<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Telemetry\TelemetryCollector;
use League\Container\ServiceProvider\AbstractServiceProvider;

class TelemetryServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        $services = [
            TelemetryCollector::class,
        ];

        return in_array($id, $services, true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(TelemetryCollector::class, function () {
            return new TelemetryCollector();
        })->setShared(true);
    }
}
