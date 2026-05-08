<?php declare(strict_types=1);

namespace Tests\Fixtures\Core;

use League\Container\ServiceProvider\AbstractServiceProvider;

class DummyServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return $id === 'dummy.service';
    }

    public function register(): void
    {
        $this->getContainer()->add('dummy.service', fn() => 'ok')->setShared(true);
    }
}
