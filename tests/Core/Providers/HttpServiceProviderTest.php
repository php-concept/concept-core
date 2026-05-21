<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Components\View\ViewResponseFactory;
use Concept\Core\Http\RequestFormat;
use Concept\Core\Http\ResponseFactory;
use Concept\Core\Providers\HttpServiceProvider;
use Illuminate\Pagination\Paginator;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use League\Container\Container;
use League\Route\Router;
use PHPUnit\Framework\TestCase;
use Concept\Core\Http\Contracts\ResponseFactoryInterface;
use Psr\Http\Message\ServerRequestInterface;

final class HttpServiceProviderTest extends TestCase
{
    public function testProvidesExpectedHttpServices(): void
    {
        $provider = new HttpServiceProvider();

        self::assertTrue($provider->provides(ServerRequestInterface::class));
        self::assertTrue($provider->provides(Router::class));
        self::assertTrue($provider->provides(RequestFormat::class));
        self::assertTrue($provider->provides(ResponseFactoryInterface::class));
        self::assertFalse($provider->provides(ResponseFactory::class));
        self::assertTrue($provider->provides(ViewResponseFactory::class));
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
        self::assertInstanceOf(ResponseFactoryInterface::class, $container->get(ResponseFactoryInterface::class));
        self::assertFalse($container->has(ResponseFactory::class));

        $container->add(ViewInterface::class, $this->createStub(ViewInterface::class));
        self::assertInstanceOf(ViewResponseFactory::class, $container->get(ViewResponseFactory::class));

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
