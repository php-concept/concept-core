<?php declare(strict_types=1);

namespace Tests\Core\Foundation;

use Concept\Core\Foundation\ContainerResolver;
use League\Container\Container;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ContainerResolverTest extends TestCase
{
    public function testTryGetReturnsServiceWhenResolvable(): void
    {
        $container = new Container();
        $container->add('token', fn () => 'ok');

        self::assertSame('ok', ContainerResolver::tryGet($container, 'token'));
    }

    public function testTryGetReturnsNullWhenResolutionFails(): void
    {
        $container = new Container();
        $container->add('broken', function (): string {
            throw new RuntimeException('missing dependency');
        });

        self::assertNull(ContainerResolver::tryGet($container, 'broken'));
    }

    public function testTryGetReturnsNullForUnknownAlias(): void
    {
        $container = new Container();

        self::assertNull(ContainerResolver::tryGet($container, 'missing.alias'));
    }
}
