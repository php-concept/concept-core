<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Caster\Caster;
use Concept\Core\Components\Caster\Contracts\CasterInterface;
use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Path\PathManager;
use Concept\Core\Events\Framework\ServiceAwaking;
use Concept\Core\Providers\Concerns\PeeksEventDispatcher;
use League\Container\ServiceProvider\AbstractServiceProvider;

class CastingServiceProvider extends AbstractServiceProvider
{
    use PeeksEventDispatcher;
    public function provides(string $id): bool
    {
        return $id == CasterInterface::class;
    }

    public function register(): void
    {
        $container = $this->getContainer();
        $container->add(CasterInterface::class, function () use ($container) {
            $this->peekEventDispatcher()?->dispatch(new ServiceAwaking(CasterInterface::class));

            /** @var PathManager $pathManager */
            $pathManager = $container->get(PathManager::class);
            /** @var ConfigInterface $config */
            $config = $this->getContainer()->get(ConfigInterface::class);

            return new Caster($pathManager, $config);
        })->setShared(true);
    }
}