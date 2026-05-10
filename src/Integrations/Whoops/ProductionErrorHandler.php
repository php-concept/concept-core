<?php declare(strict_types=1);

namespace Concept\Core\Integrations\Whoops;

use Concept\Core\Components\Logger\Contracts\LoggerInterface;
use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Http\Protocol\HttpStatusCode;
use Concept\Core\Http\RequestFormat;
use Concept\Core\Http\ResponseFactory;
use Concept\Core\Components\Path\PathManager;
use Laminas\HttpHandlerRunner\Emitter\SapiEmitter;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ServerRequestInterface;
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

        try {
            /** @var ResponseFactory $responseFactory */
            $responseFactory = $this->container->get(ResponseFactory::class);
            /** @var ServerRequestInterface $request */
            $request = $this->container->get(ServerRequestInterface::class);
            /** @var RequestFormat $requestFormat */
            $requestFormat = $this->container->get(RequestFormat::class);

            if ($requestFormat->expectsJson($request)) {
                $response = $responseFactory->jsonError($exception->getMessage(), $code);
                (new SapiEmitter())->emit($response);

                exit;
            }
        } catch (Throwable $e) {
            $this->renderFallback($this->fallbackPath, $code, $e);

            exit;
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
        /** @var PathManager $pathManager */
        $pathManager = $this->container->get(PathManager::class);
        /** @var ViewInterface $view */
        $view = $this->container->get(ViewInterface::class);

        $template = sprintf(self::TEMPLATE_ERROR_FORMAT, $code);
        $templatePath = $pathManager->get(PathManager::VIEWS_DIR , $template);
        if (!file_exists($templatePath . ViewInterface::DEFAULT_EXTENSION)) {
            $template = sprintf(self::TEMPLATE_ERROR_FORMAT, self::DEFAULT_ERROR_CODE);
        }

        return $view->render($template, ['exception' => $exception]);
    }

    private function renderFallback(string $fallbackPath, int $code, Throwable $exception): void
    {
        /** @var LoggerInterface $logger */
        $logger = $this->container->get(LoggerInterface::class);
        $logger->exception($exception);

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