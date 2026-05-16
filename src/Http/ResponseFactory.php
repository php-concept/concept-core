<?php declare(strict_types=1);

namespace Concept\Core\Http;

use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Http\Protocol\HttpHeader;
use Concept\Core\Http\Protocol\HttpStatusCode;
use Concept\Core\Http\Protocol\HttpValue;
use Concept\Core\Http\Protocol\UrlComponent;
use Laminas\Diactoros\Response;
use Laminas\Diactoros\Response\RedirectResponse;
use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class ResponseFactory implements ResponseFactoryInterface
{
    private const string PAYLOAD_STATUS = 'status';
    private const string PAYLOAD_CODE = 'code';
    private const string PAYLOAD_DATA = 'data';
    private const string PAYLOAD_MESSAGE = 'message';
    private const string PAYLOAD_ERRORS = 'errors';
    private const string PAYLOAD_STATUS_SUCCESS = 'success';
    private const string PAYLOAD_STATUS_ERROR = 'error';

    public function __construct(
        readonly private ContainerInterface $container,
        readonly private ViewInterface $view
    ) {}

    public function createResponse(int $code = HttpStatusCode::OK, string $reasonPhrase = ''): ResponseInterface
    {
        return (new Response())->withStatus($code, $reasonPhrase);
    }

    /**
     * @param string $template
     * @param array<string, mixed> $data
     * @param int $code
     * @return ResponseInterface
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function view(string $template, array $data = [], int $code = HttpStatusCode::OK): ResponseInterface
    {
        $sharedData = $this->request()->getAttribute(RequestAttribute::VIEW_PAYLOAD, []);
        if (!is_array($sharedData)) {
            $sharedData = [];
        }
        $combinedData = array_merge($sharedData, $data);

        $content = $this->view->render($template, $combinedData);

        $response = $this->createResponse($code);
        $response->getBody()->write($content);

        return $response->withHeader(HttpHeader::CONTENT_TYPE, HttpValue::HTML);
    }

    public function json(
        mixed $data,
        int $code = HttpStatusCode::OK,
        int $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    ): ResponseInterface {
        $response = $this->createResponse($code);
        $response->getBody()->write((string)json_encode($data, $jsonFlags));

        return $response->withHeader(HttpHeader::CONTENT_TYPE, HttpValue::JSON);
    }

    public function jsonSuccess(
        mixed $data = [],
        int $code = HttpStatusCode::OK,
        int $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    ): ResponseInterface {
        return $this->json([
            self::PAYLOAD_STATUS => self::PAYLOAD_STATUS_SUCCESS,
            self::PAYLOAD_CODE => $code,
            self::PAYLOAD_DATA => $data
        ], $code, $jsonFlags);
    }

    /**
     * @param string $message
     * @param int $code
     * @param array<string, mixed> $errors
     * @param int $jsonFlags
     * @return ResponseInterface
     */
    public function jsonError(
        string $message,
        int $code = HttpStatusCode::INTERNAL_SERVER_ERROR,
        array $errors = [],
        int $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    ): ResponseInterface {
        $payload = [
            self::PAYLOAD_STATUS => self::PAYLOAD_STATUS_ERROR,
            self::PAYLOAD_CODE => $code,
            self::PAYLOAD_MESSAGE => $message,
        ];

        if (!empty($errors)) {
            $payload[self::PAYLOAD_ERRORS] = $errors;
        }

        return $this->json($payload, $code, $jsonFlags);
    }

    public function redirect(string $url, int $status = HttpStatusCode::FOUND): ResponseInterface
    {
        return new RedirectResponse($url, $status);
    }

    public function back(int $status = HttpStatusCode::FOUND, string $fallback = '/'): ResponseInterface
    {
        // 1. Priority №1: Referer (always the most up to date for the browser)
        $url = $this->request()->getHeaderLine(HttpHeader::REFERER);

        // 2. Priority №2: Our pre-calculated safe URL
        if (!$url) {
            $url = $this->request()->getAttribute(RequestAttribute::SAFE_BACK_URL);
        }

        // 3. Validation and redirect
        $target = (is_string($url) && $this->isInternalUrl($url)) ? $url : $fallback;

        return new RedirectResponse($target, $status);
    }

    private function request(): ServerRequestInterface
    {
        /** @var ServerRequestInterface $request */
        $request = $this->container->get(ServerRequestInterface::class);

        return $request;
    }

    private function isInternalUrl(string $url): bool
    {
        if (empty($url)) {
            return false;
        }

        $parts = parse_url($url);
        $currentHost = $this->request()->getUri()->getHost();

        // If the URL is relative, it's internal
        if (!isset($parts[UrlComponent::HOST])) {
            return str_starts_with($url, '/');
        }

        // Host must strictly match the current application domain
        return $parts[UrlComponent::HOST] === $currentHost;
    }
}