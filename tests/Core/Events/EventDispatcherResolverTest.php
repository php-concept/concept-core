<?php declare(strict_types=1);

namespace Tests\Core\Events;

use Concept\Core\Events\EventDispatcherResolver;
use League\Container\Container;
use League\Event\EventDispatcher;
use PHPUnit\Framework\TestCase;
use Psr\EventDispatcher\EventDispatcherInterface;

final class EventDispatcherResolverTest extends TestCase
{
    public function testReturnsNullWhenDispatcherIsNotRegistered(): void
    {
        self::assertNull(EventDispatcherResolver::resolve(new Container()));
    }

    public function testReturnsLeagueDispatcherWhenBoundToInterface(): void
    {
        $container = new Container();
        $dispatcher = new EventDispatcher();
        $container->add(EventDispatcherInterface::class, $dispatcher)->setShared(true);

        self::assertSame($dispatcher, EventDispatcherResolver::resolve($container));
    }

    public function testReturnsNullWhenBindingIsNotLeagueDispatcher(): void
    {
        $container = new Container();
        $container->add(EventDispatcherInterface::class, new class implements EventDispatcherInterface {
            public function dispatch(object $event): object
            {
                return $event;
            }
        })->setShared(true);

        self::assertNull(EventDispatcherResolver::resolve($container));
    }
}
