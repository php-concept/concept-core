<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Events\EventName;
use Concept\Core\Events\Http\RouterDispatchStarted;
use Concept\Core\Events\Telemetry\ApplicationTelemetryBuffer;
use Concept\Core\Providers\EventServiceProvider;
use League\Container\Container;
use League\Event\EventDispatcher;
use PHPUnit\Framework\TestCase;
use Psr\Http\Message\ServerRequestInterface;

final class EventServiceProviderTest extends TestCase
{
    public function testRegistersSharedEventDispatcherAndBuffer(): void
    {
        $container = new Container();
        $container->add(ConfigInterface::class, $this->configStub(debug: false))->setShared(true);

        $provider = new EventServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        self::assertSame($container->get(EventDispatcherInterface::class), $container->get(EventDispatcherInterface::class));
        self::assertSame(
            $container->get(ApplicationTelemetryBuffer::class),
            $container->get(ApplicationTelemetryBuffer::class),
        );
    }

    public function testWiresTelemetryBufferWhenAppDebugIsTrue(): void
    {
        $container = new Container();
        $container->add(ConfigInterface::class, $this->configStub(debug: true))->setShared(true);
        $container->addServiceProvider(new EventServiceProvider());

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $container->get(EventDispatcherInterface::class);
        /** @var ApplicationTelemetryBuffer $buffer */
        $buffer = $container->get(ApplicationTelemetryBuffer::class);

        $request = $this->createStub(ServerRequestInterface::class);
        $dispatcher->dispatch(new RouterDispatchStarted(EventName::HTTP_ROUTER_DISPATCH_STARTED, $request));

        $entries = $buffer->all();
        self::assertCount(1, $entries);
        self::assertSame(EventName::HTTP_ROUTER_DISPATCH_STARTED, $entries[0]['name']);
    }

    public function testDoesNotWireTelemetryBufferWhenAppDebugIsFalse(): void
    {
        $container = new Container();
        $container->add(ConfigInterface::class, $this->configStub(debug: false))->setShared(true);
        $container->addServiceProvider(new EventServiceProvider());

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $container->get(EventDispatcherInterface::class);
        /** @var ApplicationTelemetryBuffer $buffer */
        $buffer = $container->get(ApplicationTelemetryBuffer::class);

        $request = $this->createStub(ServerRequestInterface::class);
        $dispatcher->dispatch(new RouterDispatchStarted(EventName::HTTP_ROUTER_DISPATCH_STARTED, $request));

        self::assertCount(0, $buffer->all());
    }

    public function testWiresListenersWhenResolvedThroughServiceProviderAggregate(): void
    {
        $container = new Container();
        $container->add(ConfigInterface::class, $this->configStub(debug: true))->setShared(true);

        $provider = new EventServiceProvider();
        $container->addServiceProvider($provider);

        /** @var EventDispatcher $dispatcher */
        $dispatcher = $container->get(EventDispatcherInterface::class);
        /** @var ApplicationTelemetryBuffer $buffer */
        $buffer = $container->get(ApplicationTelemetryBuffer::class);

        $request = $this->createStub(ServerRequestInterface::class);
        $dispatcher->dispatch(new RouterDispatchStarted(EventName::HTTP_ROUTER_DISPATCH_STARTED, $request));

        self::assertCount(1, $buffer->all());
    }

    private function configStub(bool $debug): ConfigInterface
    {
        return new class ($debug) implements ConfigInterface {
            public function __construct(private readonly bool $debug) {}

            public function get(string $key, mixed $default = null): mixed
            {
                return $default;
            }

            public function set(string $key, mixed $value): void {}

            public function has(string $key): bool
            {
                return false;
            }

            public function all(): array
            {
                return [];
            }

            public function getString(string $key, string $default = ''): string
            {
                return $default;
            }

            public function getInt(string $key, int $default = 0): int
            {
                return $default;
            }

            public function getBool(string $key, bool $default = false): bool
            {
                return $key === 'app.debug' ? $this->debug : $default;
            }
        };
    }
}
