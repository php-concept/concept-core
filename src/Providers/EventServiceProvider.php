<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Events\EventName;
use Concept\Core\Events\Telemetry\ApplicationTelemetryBuffer;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Event\EventDispatcher;
use Psr\EventDispatcher\EventDispatcherInterface;

class EventServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        $services = [
            EventDispatcherInterface::class,
            ApplicationTelemetryBuffer::class,
        ];

        return in_array($id, $services, true);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(ApplicationTelemetryBuffer::class, function () {
            return new ApplicationTelemetryBuffer();
        })->setShared(true);

        $container->add(EventDispatcherInterface::class, function () use ($container) {
            $dispatcher = new EventDispatcher();

            if ($container->has(ConfigInterface::class)) {
                /** @var ConfigInterface $config */
                $config = $container->get(ConfigInterface::class);

                if ($config->getBool('app.debug')) {
                    /** @var ApplicationTelemetryBuffer $buffer */
                    $buffer = $container->get(ApplicationTelemetryBuffer::class);

                    foreach (EventName::telemetryEvents() as $eventName) {
                        $dispatcher->subscribeTo($eventName, function (object $event) use ($buffer): void {
                            $buffer->record($event);
                        });
                    }
                }
            }

            return $dispatcher;
        })->setShared(true);
    }
}
