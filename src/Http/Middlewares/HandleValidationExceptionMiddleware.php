<?php declare(strict_types=1);

namespace Concept\Core\Http\Middlewares;

use Concept\Core\Components\Validator\Exceptions\ValidationException;
use Concept\Core\Http\Protocol\HttpStatusCode;
use Concept\Core\Http\RequestFormat;
use Concept\Core\Http\SessionKey;
use Concept\Core\Http\ResponseFactory;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class HandleValidationExceptionMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly ResponseFactory $responseFactory,
        private readonly RequestFormat $requestFormat,
        private readonly FlashBagInterface $flashBag
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        try {
            return $handler->handle($request);
        } catch (ValidationException $e) {
            if ($this->requestFormat->expectsJson($request)) {
                return $this->responseFactory->jsonError(
                    $e->getMessage(),
                    HttpStatusCode::UNPROCESSABLE_ENTITY,
                    $e->getErrors()
                );
            }

            $this->flashBag->set('error', $e->getMessage());
            $this->flashBag->set(SessionKey::VALIDATION_ERRORS, $e->getErrors());
            $this->flashBag->set(SessionKey::VALIDATION_DATA, $e->getOldData());

            return $this->responseFactory->back();
        }
    }
}