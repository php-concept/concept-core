<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Caster\Caster;
use Concept\Core\Components\Caster\Contracts\CasterInterface;
use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Path\PathManager;
use League\Container\ServiceProvider\AbstractServiceProvider;

class CastingServiceProvider extends AbstractServiceProvider
{
    public function provides(string $id): bool
    {
        return $id == CasterInterface::class;
    }

    public function register(): void
    {
        $container = $this->getContainer();
        $container->add(CasterInterface::class, function () use ($container) {
            /** @var PathManager $pathManager */
            $pathManager = $container->get(PathManager::class);
            /** @var ConfigInterface $config */
            $config = $this->getContainer()->get(ConfigInterface::class);

            return new Caster($pathManager, $config);
        })->setShared(true);
    }
}