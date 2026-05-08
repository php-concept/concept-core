<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Logger\DebugLogger;
use Concept\Core\Components\Path\PathManager;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

class DebugLoggerServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    public function provides(string $id): bool
    {
        $services = [
            DebugLogger::class,
        ];

        return in_array($id, $services);
    }

    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(DebugLogger::class, function () use ($container) {
            /** @var PathManager $pathManager */
            $pathManager = $container->get(PathManager::class);

            return new DebugLogger($pathManager);
        })->setShared(true);
    }

    public function boot(): void
    {
        /** @var DebugLogger $debugLogger */
        $debugLogger = $this->getContainer()->get(DebugLogger::class);
        DebugLogger::setInstance($debugLogger);
    }
}
