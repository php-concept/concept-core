<?php declare(strict_types=1);

namespace Tests\Fixtures\Core;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class SecondBootableProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public function provides(string $id): bool
    {
        return $id === 'provider.second';
    }

    public function register(): void
    {
        ProviderRegistrationTracker::$events[] = 'second.register';
        $this->getContainer()->add('provider.second', fn() => 'second')->setShared(true);
    }

    public function boot(): void
    {
        ProviderRegistrationTracker::$events[] = 'second.boot';
        ProviderRegistrationTracker::$loopGuard++;
        if (ProviderRegistrationTracker::$loopGuard > 20) {
            throw new \RuntimeException('Potential provider registration loop detected.');
        }
    }
}

