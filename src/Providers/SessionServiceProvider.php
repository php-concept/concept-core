<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Telemetry\TelemetryEvent;
use Concept\Core\Telemetry\TelemetryTrait;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpFoundation\Session\Storage\Handler\NativeFileSessionHandler;
use Symfony\Component\HttpFoundation\Session\Storage\NativeSessionStorage;

class SessionServiceProvider extends AbstractServiceProvider
{
    use TelemetryTrait;

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
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, SessionInterface::class);

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
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, FlashBagInterface::class);

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
            'cookie_lifetime' => $config->getInt(ConfigKey::SESSION_COOKIE_LIFETIME, 0),
            'cookie_path' => $config->getString(ConfigKey::SESSION_COOKIE_PATH, '/'),
            'cookie_secure' => $config->getBool(ConfigKey::SESSION_COOKIE_SECURE, false),
            'cookie_httponly' => $config->getBool(ConfigKey::SESSION_COOKIE_HTTPONLY, true),
            'use_only_cookies' => $config->getBool(ConfigKey::SESSION_USE_ONLY_COOKIES, true),
            'cookie_domain'   => $config->getString(ConfigKey::SESSION_DOMAIN, ''),
            'cookie_samesite' => $config->getString(ConfigKey::SESSION_SAMESITE, 'Lax'),
            'use_strict_mode' => $config->getBool(ConfigKey::SESSION_USE_STRICT_MODE, true),
        ];
    }
}
