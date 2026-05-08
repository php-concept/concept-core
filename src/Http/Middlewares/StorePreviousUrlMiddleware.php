<?php declare(strict_types=1);

namespace Concept\Core\Http\Middlewares;

use Concept\Core\Http\Protocol\HttpHeader;
use Concept\Core\Http\Protocol\HttpMethod;
use Concept\Core\Http\Protocol\HttpValue;
use Concept\Core\Http\RequestAttribute;
use Concept\Core\Http\SessionKey;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

class StorePreviousUrlMiddleware implements MiddlewareInterface
{
    public function __construct(private readonly SessionInterface $session) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $session = $this->session;
        $method = $request->getMethod();
        $uri = (string)$request->getUri();

        if ($method === HttpMethod::GET && !$this->isAjax($request)) {
            $currentInSession = $session->get(SessionKey::CURRENT_URL);

            if ($uri !== $currentInSession) {
                $session->set(SessionKey::PREVIOUS_URL, $currentInSession);
                $session->set(SessionKey::CURRENT_URL, $uri);
            }
        }

        /**
         * Define "safe back URL" for THIS request:
         * - If we are in GET (navigation) -> redirect to PREVIOUS (previous page).
         * - If we are in POST (validation) -> redirect to CURRENT (form page).
         */
        $backUrl = ($method === HttpMethod::GET)
            ? $session->get(SessionKey::PREVIOUS_URL)
            : $session->get(SessionKey::CURRENT_URL);

        return $handler->handle(
            $request->withAttribute(RequestAttribute::SAFE_BACK_URL, $backUrl)
        );
    }

    private function isAjax(ServerRequestInterface $request): bool
    {
        return $request->getHeaderLine(HttpHeader::X_REQUESTED_WITH) === HttpValue::XML_HTTP_REQUEST;
    }
}