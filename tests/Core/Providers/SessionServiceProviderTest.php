<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Providers\SessionServiceProvider;
use League\Container\Container;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

final class SessionServiceProviderTest extends TestCase
{
    public function testProvidesSessionAndFlashBagServices(): void
    {
        $provider = new SessionServiceProvider();

        self::assertTrue($provider->provides(SessionInterface::class));
        self::assertTrue($provider->provides(FlashBagInterface::class));
        self::assertFalse($provider->provides('unknown.service'));
    }

    public function testRegisterBindsStartedSessionAndFlashBag(): void
    {
        $container = new Container();
        $container->add(ConfigInterface::class, new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed { return $default; }
            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string { return $default; }
            public function getInt(string $key, int $default = 0): int { return $default; }
            public function getBool(string $key, bool $default = false): bool { return $default; }
        })->setShared(true);

        $provider = new SessionServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        /** @var SessionInterface $session */
        $session = $container->get(SessionInterface::class);
        self::assertTrue($session->isStarted());

        /** @var FlashBagInterface $flash */
        $flash = $container->get(FlashBagInterface::class);
        $flash->set('info', 'ok');
        self::assertSame(['ok'], $session->getFlashBag()->get('info'));
    }
}
