<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Masker\Contracts\MaskerInterface;
use Concept\Core\Components\Masker\DataMasker;
use Concept\Core\Components\Masker\RegexMasker;
use Concept\Core\Events\Framework\ServiceAwakening;
use Concept\Core\Providers\Concerns\PeeksEventDispatcher;
use League\Container\ServiceProvider\AbstractServiceProvider;

class MaskerServiceProvider extends AbstractServiceProvider
{
    use PeeksEventDispatcher;

    public function provides(string $id): bool
    {
        return $id === MaskerInterface::class;
    }

    public function register(): void
    {
        $this->getContainer()->add(MaskerInterface::class, function () {
            $this->peekEventDispatcher()?->dispatch(new ServiceAwakening(MaskerInterface::class));

            $masker = new DataMasker();
            $masker->addRule(new RegexMasker());

            return $masker;
        })->setShared(true);
    }
}