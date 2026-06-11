<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Foundation\ContainerResolver;
use Concept\Core\Http\RequestFormat;
use Concept\Core\Integrations\Whoops\EarlyBootstrapFallbackHandler;
use Concept\Core\Integrations\Whoops\ErrorLogHandler;
use Concept\Core\Integrations\Whoops\PhpErrorLogHandler;
use Concept\Core\Integrations\Whoops\ProductionErrorHandler;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Foundation\PathManager;
use Concept\Core\Foundation\PathName;
use Concept\Core\Php\PhpSapi;
use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Telemetry\TelemetryEvent;
use Concept\Core\Telemetry\TelemetryTrait;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as Whoops;

class ErrorHandlerServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    use TelemetryTrait;

    public function provides(string $id): bool
    {
        $services = [
            Whoops::class,
        ];

        return in_array($id, $services);
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
        $container = $this->getContainer();

        /** @var Whoops|null $whoops */
        $whoops = ContainerResolver::tryGet($container, Whoops::class);
        if ($whoops === null) {
            return;
        }

        $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, Whoops::class);

        try {
            $whoops->clearHandlers();

            $whoops->pushHandler(function (Throwable $exception) {
                $handler = new PhpErrorLogHandler();
                $handler->setException($exception);

                return $handler->handle();
            });

            $this->registerRenderHandlers($container, $whoops);

            $whoops->appendHandler(function (Throwable $exception) use ($container) {
                $handler = new ErrorLogHandler($container);
                $handler->setException($exception);

                return $handler->handle();
            });

            $whoops->register();
        } catch (Throwable) {
            $this->restoreEarlyHandlers($container, $whoops);
        }
    }

    private function registerRenderHandlers(ContainerInterface $container, Whoops $whoops): void
    {
        /** @var ServerRequestInterface|null $request */
        $request = ContainerResolver::tryGet($container, ServerRequestInterface::class);
        /** @var RequestFormat|null $requestFormat */
        $requestFormat = ContainerResolver::tryGet($container, RequestFormat::class);

        if ($request !== null && $requestFormat !== null && $requestFormat->expectsJson($request)) {
            $whoops->appendHandler(new JsonResponseHandler());

            return;
        }

        $this->registerHandlers($container, $whoops);
    }

    private function registerHandlers(ContainerInterface $container, Whoops $whoops): void
    {
        if ($this->isCli()) {
            $whoops->appendHandler(new PlainTextHandler());

            return;
        }

        /** @var ConfigInterface|null $config */
        $config = ContainerResolver::tryGet($container, ConfigInterface::class);
        if ($config === null) {
            $this->appendEarlyWebFallback($container, $whoops);

            return;
        }

        if ($config->getBool(ConfigKey::APP_DEBUG, false)) {
            $whoops->appendHandler(new PrettyPageHandler());

            return;
        }

        /** @var PathManager|null $pathManager */
        $pathManager = ContainerResolver::tryGet($container, PathManager::class);
        if ($pathManager === null) {
            $this->appendEarlyWebFallback($container, $whoops);

            return;
        }

        $fallbackPath = $pathManager->get(PathName::ERRORS_FALLBACK_VIEWS);

        $whoops->appendHandler(function (Throwable $exception) use ($container, $fallbackPath) {
            $handler = new ProductionErrorHandler($container, $fallbackPath);
            $handler->setException($exception);

            return $handler->handle();
        });
    }

    private function restoreEarlyHandlers(ContainerInterface $container, Whoops $whoops): void
    {
        $whoops->clearHandlers();

        if (!$this->isCli()) {
            $this->appendEarlyWebFallback($container, $whoops);
        } elseif (($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
            $whoops->appendHandler(new PrettyPageHandler());
        } else {
            $whoops->appendHandler(new PlainTextHandler());
        }

        $whoops->pushHandler(new PhpErrorLogHandler());
        $whoops->register();
    }

    private function appendEarlyWebFallback(ContainerInterface $container, Whoops $whoops): void
    {
        /** @var PathManager|null $pathManager */
        $pathManager = ContainerResolver::tryGet($container, PathManager::class);
        if ($pathManager === null) {
            return;
        }

        $whoops->appendHandler(new EarlyBootstrapFallbackHandler($pathManager->root()));
    }

    protected function isCli(): bool
    {
        return PHP_SAPI === PhpSapi::CLI;
    }
}
