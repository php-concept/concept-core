<?php declare(strict_types=1);

namespace Tests\Core\Http;

use Concept\Core\Http\RequestProxy;
use Laminas\Diactoros\ServerRequest;
use League\Container\Container;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class RequestProxyTest extends TestCase
{
    public function testRequestReturnsCurrentContainerBinding(): void
    {
        $container = new Container();
        $initial = new ServerRequest();
        $updated = $initial->withAttribute('locale', 'uk');

        $container->add(ServerRequestInterface::class, $initial, true);

        $proxy = new RequestProxy($container);
        self::assertSame($initial, $proxy->get());

        $container->add(ServerRequestInterface::class, $updated, true);
        self::assertSame($updated, $proxy->get());
        self::assertSame('uk', $proxy->get()->getAttribute('locale'));
    }
}
