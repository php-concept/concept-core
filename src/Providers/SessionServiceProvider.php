<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Events\Framework\ServiceAwakening;
use Concept\Core\Providers\Concerns\PeeksEventDispatcher;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

class SessionServiceProvider extends AbstractServiceProvider
{
    use PeeksEventDispatcher;

    public function provides(string $id): bool
    {
        $services = [
            SessionInterface::class,
            FlashBagInterface::class,
        ];

        return in_array($id, $services);
    }

    public function register(): void
    {
        $container = $this->getContainer();
        $container->add(SessionInterface::class, function () use ($container) {
            $this->peekEventDispatcher()?->dispatch(new ServiceAwakening(SessionInterface::class));

            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);
            $sessionOptions = $this->getSessionOptions($config);

            $storage = new NativeSessionStorage(
                $sessionOptions,
                new NativeFileSessionHandler()
            );

            $session = new Session($storage);

            if (!$session->isStarted()) {
                $session->start();
            }

            return $session;
        })->setShared(true);

        $container->add(FlashBagInterface::class, function () use ($container) {
            $this->peekEventDispatcher()?->dispatch(new ServiceAwakening(FlashBagInterface::class));

            /** @var Session $session */
            $session = $container->get(SessionInterface::class);

            return $session->getFlashBag();
        })->setShared(true);
    }

    /**
     * @param ConfigInterface $config
     * @return array<string, mixed>
     */
    private function getSessionOptions(ConfigInterface $config): array
    {
        return [
            'cookie_lifetime' => $config->getInt('session.cookie_lifetime', 0),
            'cookie_path' => $config->getString('session.cookie_path', '/'),
            'cookie_secure' => $config->getBool('session.cookie_secure', false),
            'cookie_httponly' => $config->getBool('session.cookie_httponly', true),
            'use_only_cookies' => $config->getBool('session.use_only_cookies', true),
            'cookie_domain'   => $config->getString('session.domain', ''),
            'cookie_samesite' => $config->getString('session.samesite', 'Lax'),
            'use_strict_mode' => $config->getBool('session.use_strict_mode', true),
        ];
    }
}
