<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Http\RequestFormat;
use Concept\Core\Providers\HttpServiceProvider;
use League\Container\Container;
use League\Route\Router;
use Illuminate\Pagination\Paginator;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class HttpServiceProviderTest extends TestCase
{
    public function testProvidesExpectedHttpServices(): void
    {
        $provider = new HttpServiceProvider();

        self::assertTrue($provider->provides(ServerRequestInterface::class));
        self::assertTrue($provider->provides(Router::class));
        self::assertTrue($provider->provides(RequestFormat::class));
    }

    public function testRegisterAndBootBindServicesAndConfigurePaginatorResolvers(): void
    {
        $container = new Container();

        // prebind request to make paginator assertions deterministic
        $request = (new ServerRequest())
            ->withUri(new Uri('https://app.test/list'))
            ->withQueryParams(['page' => '4']);
        $container->add(ServerRequestInterface::class, $request, true);

        $provider = new HttpServiceProvider();
        $provider->setContainer($container);
        $provider->register();
        $provider->boot();

        self::assertInstanceOf(RequestFormat::class, $container->get(RequestFormat::class));
        self::assertInstanceOf(Router::class, $container->get(Router::class));

        self::assertSame(4, Paginator::resolveCurrentPage());
        self::assertSame('/list', Paginator::resolveCurrentPath());
    }

    public function testRegisterBuildsServerRequestFromGlobalsWhenNotPrebound(): void
    {
        $container = new Container();

        $provider = new HttpServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        self::assertInstanceOf(ServerRequestInterface::class, $container->get(ServerRequestInterface::class));
    }
}
