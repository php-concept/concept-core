<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Logger\Contracts\LoggerInterface;
use Concept\Core\Components\Logger\Logger;
use Concept\Core\Components\DataMasker\Contracts\DataMaskerInterface;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Foundation\PathManager;
use Concept\Core\Foundation\PathName;
use Concept\Core\Telemetry\TelemetryEvent;
use Concept\Core\Telemetry\TelemetryTrait;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Monolog\Handler\RotatingFileHandler;
use Monolog\Level;
use Monolog\Logger as Monolog;
use Monolog\Processor\PsrLogMessageProcessor;
use Throwable;

class LogServiceProvider extends AbstractServiceProvider
{
    use TelemetryTrait;

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
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, LoggerInterface::class);

            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            $monolog = new Monolog($config->getString(ConfigKey::LOG_NAME));
            $this->setup($monolog);

            /** @var DataMaskerInterface|null $masker */
            $masker = $container->has(DataMaskerInterface::class)
                ? $container->get(DataMaskerInterface::class)
                : null;

            return new Logger($monolog, $masker);
        })->setShared(true);
    }

    private function setup(Monolog $monolog): void
    {
        $container = $this->getContainer();

        /** @var PathManager $pathManager */
        $pathManager = $container->get(PathManager::class);
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);

        $logsPath = $pathManager->get(PathName::LOGS, 'app.log');
        $logLevelName = $config->getString(ConfigKey::LOG_LEVEL, 'debug');
        try {
            /** @phpstan-ignore-next-line */
            $logLevel = Level::fromName($logLevelName);
        } catch (Throwable $exception) {
            $logLevel = Level::Debug;
        }

        $maxFiles = $config->getInt(ConfigKey::LOG_MAX_FILES, 7);

        $monolog->pushHandler(new RotatingFileHandler($logsPath, $maxFiles, $logLevel));
        $monolog->pushProcessor(new PsrLogMessageProcessor());
    }
}
