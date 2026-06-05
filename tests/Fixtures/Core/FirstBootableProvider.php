<?php declare(strict_types=1);

namespace Tests\Fixtures\Core;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class FirstBootableProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public function provides(string $id): bool
    {
        return $id === 'provider.first';
    }

    public function register(): void
    {
        ProviderRegistrationTracker::$events[] = 'first.register';
        $this->getContainer()->add('provider.first', fn() => 'first')->setShared(true);
    }

    public function boot(): void
    {
        ProviderRegistrationTracker::$events[] = 'first.boot';
        ProviderRegistrationTracker::$loopGuard++;
        if (ProviderRegistrationTracker::$loopGuard > 20) {
            throw new \RuntimeException('Potential provider registration loop detected.');
        }
    }
}

