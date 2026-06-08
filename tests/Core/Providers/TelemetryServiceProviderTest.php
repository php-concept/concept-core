<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Telemetry\TelemetryCollector;
use Concept\Core\Providers\TelemetryServiceProvider;
use League\Container\Container;
use PHPUnit\Framework\TestCase;

final class TelemetryServiceProviderTest extends TestCase
{
    public function testProvidesTelemetryCollector(): void
    {
        $provider = new TelemetryServiceProvider();

        self::assertTrue($provider->provides(TelemetryCollector::class));
        self::assertFalse($provider->provides('telemetry.unknown'));
    }

    public function testRegisterBuildsSharedTelemetryCollector(): void
    {
        $container = new Container();
        $provider = new TelemetryServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        $first = $container->get(TelemetryCollector::class);
        $second = $container->get(TelemetryCollector::class);

        self::assertInstanceOf(TelemetryCollector::class, $first);
        self::assertSame($first, $second);
    }
}
