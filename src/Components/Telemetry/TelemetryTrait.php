<?php

namespace Concept\Core\Components\Telemetry;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use League\Container\DefinitionContainerInterface;
use Throwable;

trait TelemetryTrait
{
    private function telemetry(): ?TelemetryCollector
    {
        /** @var DefinitionContainerInterface $container */
        $container = $this->getContainer();
        if (!$container->has(TelemetryCollector::class)) {
            return null;
        }

        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);

        if (!$config->getBool('telemetry.enabled', false)) {
            return null;
        }

        try {
            /** @var TelemetryCollector $telemetryCollector */
            $telemetryCollector = $container->get(TelemetryCollector::class);
        } catch (Throwable) {
            $telemetryCollector = null;
        }

        return $telemetryCollector;
    }
}