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

    public function testReturnsDispatcherWhenBoundToInterface(): void
    {
        $container = new Container();
        $dispatcher = new class implements EventDispatcherInterface {
            public function dispatch(object $event): object { return $event; }
        };
        $container->add(EventDispatcherInterface::class, $dispatcher)->setShared(true);

        self::assertSame($dispatcher, EventDispatcherResolver::resolve($container));
    }

}
