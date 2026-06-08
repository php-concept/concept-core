<?php declare(strict_types=1);

namespace Concept\Core\Integrations\Whoops;

use Concept\Core\Foundation\ContainerResolver;
use Concept\Core\Components\Logger\Contracts\LoggerInterface;
use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Http\Protocol\HttpStatusCode;
use Concept\Core\Http\RequestFormat;
use Concept\Core\Http\Contracts\ResponseFactoryInterface;
use Concept\Core\Foundation\PathManager;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
use RuntimeException;
use Throwable;
use Whoops\Handler\Handler;

class ProductionErrorHandler extends Handler
{
    private const int MIN_ERROR_CODE = 400;
    private const int MAX_ERROR_CODE = 599;

    private const int DEFAULT_ERROR_CODE = HttpStatusCode::INTERNAL_SERVER_ERROR;
    private const string TEMPLATE_ERROR_FORMAT = 'errors/%s';
    private const string FALLBACK_FILE_FORMAT = '%s/%s.php';
    private const string HTML_CRITICAL_ERROR =
        '<h1>%s %s</h1><p>Something went wrong and the error page could not be loaded.</p>';

    public function __construct(
        private readonly ContainerInterface $container,
        private readonly string $fallbackPath
    ) {}

    public function handle(): int
    {
        $exception = $this->getException();
        $code = $this->prepareResponseCode($exception);

        /** @var ResponseFactoryInterface|null $responseFactory */
        $responseFactory = ContainerResolver::tryGet($this->container, ResponseFactoryInterface::class);
        /** @var ServerRequestInterface|null $request */
        $request = ContainerResolver::tryGet($this->container, ServerRequestInterface::class);
        /** @var RequestFormat|null $requestFormat */
        $requestFormat = ContainerResolver::tryGet($this->container, RequestFormat::class);

        if ($responseFactory !== null && $request !== null && $requestFormat !== null) {
            try {
                if ($requestFormat->expectsJson($request)) {
                    $response = $responseFactory->jsonError($exception->getMessage(), $code);
                    (new SapiEmitter())->emit($response);

                    exit;
                }
            } catch (Throwable $e) {
                $this->renderFallback($this->fallbackPath, $code, $e);

                exit;
            }
        }

        if (!headers_sent()) {
            http_response_code($code);
        }

        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        try {
            echo $this->renderErrorPage($exception, $code);
        } catch (Throwable $e) {
            $this->renderFallback($this->fallbackPath, $code, $e);
        }

        exit;
    }

    private function prepareResponseCode(Throwable $exception): int
    {
        $code = $exception->getCode();
        if (method_exists($exception, 'getStatusCode')) {
            $code = $exception->getStatusCode();
        }

        if (!is_numeric($code) || $code < self::MIN_ERROR_CODE || $code > self::MAX_ERROR_CODE) {
            $code = HttpStatusCode::INTERNAL_SERVER_ERROR;
        }

        return (int)$code;
    }

    private function renderErrorPage(Throwable $exception, int $code): string
    {
        /** @var PathManager|null $pathManager */
        $pathManager = ContainerResolver::tryGet($this->container, PathManager::class);
        /** @var ViewInterface|null $view */
        $view = ContainerResolver::tryGet($this->container, ViewInterface::class);

        if ($pathManager === null || $view === null) {
            throw new RuntimeException('View services are not available.');
        }

        $template = sprintf(self::TEMPLATE_ERROR_FORMAT, $code);
        try {
            return $view->render($template, ['exception' => $exception]);
        } catch (Throwable $e) {
            $template = sprintf(self::TEMPLATE_ERROR_FORMAT, self::DEFAULT_ERROR_CODE);

            return $view->render($template, ['exception' => $exception]);
        }
    }

    private function renderFallback(string $fallbackPath, int $code, Throwable $exception): void
    {
        /** @var LoggerInterface|null $logger */
        $logger = ContainerResolver::tryGet($this->container, LoggerInterface::class);
        $logger?->exception($exception);

        $file = sprintf(self::FALLBACK_FILE_FORMAT, $fallbackPath, $code);
        if (!file_exists($file)) {
            $file = sprintf(self::FALLBACK_FILE_FORMAT, $fallbackPath, self::DEFAULT_ERROR_CODE);
        }

        if (file_exists($file)) {
            $exception = $this->getException();
            include $file;
        } else {
            $statusText = HttpStatusCode::getReasonPhrase($code);
            echo sprintf(self::HTML_CRITICAL_ERROR, $code, $statusText);
        }
    }
}
