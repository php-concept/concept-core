<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Http\RequestFormat;
use Concept\Core\Integrations\Whoops\ErrorLogHandler;
use Concept\Core\Integrations\Whoops\ProductionErrorHandler;
use Concept\Core\Components\Path\PathManager;
use Concept\Core\Components\Config\Contracts\ConfigInterface;
use League\Container\DefinitionContainerInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Psr\Container\ContainerExceptionInterface;
use Psr\Container\NotFoundExceptionInterface;
use Psr\Http\Message\ServerRequestInterface;
use Throwable;
use Whoops\Handler\JsonResponseHandler;
use Whoops\Handler\PlainTextHandler;
use Whoops\Handler\PrettyPageHandler;
use Whoops\Run as Whoops;

class ErrorHandlerServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    private const string PHP_SAPI_CLI = 'cli';

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

        /** @var Whoops $whoops */
        $whoops = $this->getContainer()->get(Whoops::class);
        $whoops->clearHandlers();

        if ($container->has(ServerRequestInterface::class) && $container->has(RequestFormat::class)) {
            /** @var ServerRequestInterface $request */
            $request = $this->getContainer()->get(ServerRequestInterface::class);
            /** @var RequestFormat $requestFormat */
            $requestFormat = $this->getContainer()->get(RequestFormat::class);

            if ($requestFormat->expectsJson($request)) {
                $whoops->pushHandler(new JsonResponseHandler());
            } else {
                $this->registerHandlers($container, $whoops);
            }
        } else {
            $this->registerHandlers($container, $whoops);
        }

        $whoops->pushHandler(function (Throwable $exception) use ($container) {
            $handler = new ErrorLogHandler($container);
            $handler->setException($exception);

            return $handler->handle();
        });

        $whoops->register();
    }

    /**
     * @param DefinitionContainerInterface $container
     * @param Whoops $whoops
     * @return void
     * @throws ContainerExceptionInterface
     * @throws NotFoundExceptionInterface
     */
    private function registerHandlers(DefinitionContainerInterface $container, Whoops $whoops): void
    {
        /** @var ConfigInterface $config */
        $config = $this->getContainer()->get(ConfigInterface::class);

        if ($this->isCli()) {
            $whoops->pushHandler(new PlainTextHandler());

            return;
        }

        if ($config->getBool('app.debug', false)) {
            $whoops->pushHandler(new PrettyPageHandler());

            return;
        }

        /** @var PathManager $pathManager */
        $pathManager = $container->get(PathManager::class);
        $fallbackPath = $pathManager->get(PathManager::ERRORS_FALLBACK_VIEWS_DIR);

        $whoops->pushHandler(function (Throwable $exception) use ($container, $fallbackPath) {
            $handler = new ProductionErrorHandler($container, $fallbackPath);
            $handler->setException($exception);

            return $handler->handle();
        });
    }

    protected function isCli(): bool
    {
        return PHP_SAPI === self::PHP_SAPI_CLI;
    }
}
