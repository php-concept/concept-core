<?php declare(strict_types=1);

namespace Tests\Fixtures\Core;

use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

final class CrossResolvingBootableProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public function provides(string $id): bool
    {
        return $id === 'provider.cross';
    }

    public function register(): void
    {
        ProviderRegistrationTracker::$events[] = 'cross.register';
        $this->getContainer()->add('provider.cross', fn() => 'cross')->setShared(true);
    }

    public function boot(): void
    {
        ProviderRegistrationTracker::$events[] = 'cross.boot';
        ProviderRegistrationTracker::$loopGuard++;
        if (ProviderRegistrationTracker::$loopGuard > 20) {
            throw new \RuntimeException('Potential provider registration loop detected.');
        }

        // Cross-resolution in boot should not trigger re-registration loops.
        if ($this->getContainer()->has('provider.first')) {
            $this->getContainer()->get('provider.first');
        }
        if ($this->getContainer()->has('provider.second')) {
            $this->getContainer()->get('provider.second');
        }
    }
}

