<?php declare(strict_types=1);

namespace Concept\Core\Events;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;

/**
 * Resolves the shared {@see EventDispatcher} from the container without throwing when events are disabled.
 */
final class EventDispatcherResolver
{
    public static function resolve(?ContainerInterface $container): ?EventDispatcherInterface
    {
        if (!$container || !$container->has(EventDispatcherInterface::class)) {
            return null;
        }

        $dispatcher = $container->get(EventDispatcherInterface::class);

        return $dispatcher instanceof EventDispatcherInterface ? $dispatcher : null;
    }
}
