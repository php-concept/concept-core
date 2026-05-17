<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Path\PathManager;
use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Logger\Logger;
use Concept\Core\Components\Logger\Contracts\LoggerInterface;
use Concept\Core\Events\Framework\ServiceAwaking;
use Concept\Core\Providers\Concerns\PeeksEventDispatcher;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger as Monolog;
use Monolog\Processor\PsrLogMessageProcessor;
use Throwable;

class LogServiceProvider extends AbstractServiceProvider
{
    use PeeksEventDispatcher;

    public function provides(string $id): bool
    {
        $services = [
            LoggerInterface::class,
        ];

        return in_array($id, $services);
    }

    public function register(): void
    {
        $container = $this->getContainer();
        $container->add(LoggerInterface::class, function () use ($container) {
            $this->peekEventDispatcher()?->dispatch(new ServiceAwaking(LoggerInterface::class));

            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            $monolog = new Monolog($config->getString('log.name'));
            $this->setup($monolog);

            return new Logger($monolog);
        })->setShared(true);
    }

    private function setup(Monolog $monolog): void
    {
        $container = $this->getContainer();

        /** @var PathManager $pathManager */
        $pathManager = $container->get(PathManager::class);
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);

        $logsPath = $pathManager->get(PathManager::LOGS_DIR, 'app.log');
        $logLevelName = $config->getString('log.level', 'debug');
        try {
            /** @phpstan-ignore-next-line */
            $logLevel = Level::fromName($logLevelName);
        } catch (Throwable $exception) {
            $logLevel = Level::Debug;
        }

        $maxFiles = $config->getInt('log.max_files', 7);

        $monolog->pushHandler(new RotatingFileHandler($logsPath, $maxFiles, $logLevel));
        $monolog->pushProcessor(new PsrLogMessageProcessor());
    }
}
