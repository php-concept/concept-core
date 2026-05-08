<?php declare(strict_types=1);

namespace Concept\Core\Http\Middlewares;

use Concept\Core\Http\Exceptions\Security\CsrfException;
use Concept\Core\Http\Protocol\HttpHeader;
use Concept\Core\Http\Protocol\HttpMethod;
use Concept\Core\Http\SessionKey;
use Concept\Core\Components\Csrf\CsrfTokenManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;

class VerifyCsrfTokenMiddleware implements MiddlewareInterface
{
    private const array PROTECTED_METHODS = [HttpMethod::POST, HttpMethod::PUT, HttpMethod::DELETE];

    public function __construct(
        private readonly CsrfTokenManager $csrfTokenManager
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        if (!in_array($request->getMethod(), self::PROTECTED_METHODS, true)) {
            return $handler->handle($request);
        }

        $token = $this->getTokenFromRequest($request);

        if (!$this->csrfTokenManager->validate($token)) {
            throw new CsrfException();
        }

        return $handler->handle($request);
    }

    private function getTokenFromRequest(ServerRequestInterface $request): ?string
    {
        $parsedBody = $request->getParsedBody();

        if (is_array($parsedBody) && isset($parsedBody[SessionKey::CSRF_TOKEN])) {
            $token = $parsedBody[SessionKey::CSRF_TOKEN];
            if (is_string($token)) {
                return $token;
            }
        }

        if ($request->hasHeader(HttpHeader::X_CSRF_TOKEN)) {
            return $request->getHeaderLine(HttpHeader::X_CSRF_TOKEN);
        }

        if ($request->hasHeader(HttpHeader::X_XSRF_TOKEN)) {
            $token = $request->getHeaderLine(HttpHeader::X_XSRF_TOKEN);
            return urldecode($token);
        }

        return null;
    }
}
