<?php declare(strict_types=1);

namespace Concept\Core\Http\Middlewares;

use Concept\Core\Http\RequestAttribute;
use Concept\Core\Http\SessionKey;
use Concept\Core\Http\ViewKey;
use Concept\Core\Components\Csrf\CsrfTokenManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Symfony\Component\HttpFoundation\Session\Flash\FlashBagInterface;

class ShareViewDataMiddleware implements MiddlewareInterface
{
    public function __construct(
        private readonly FlashBagInterface $flashBag,
        private readonly CsrfTokenManager $csrfTokenManager
    ) {}

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $viewContext = [
            ViewKey::ERRORS => $this->flashBag->get(SessionKey::VALIDATION_ERRORS),
            ViewKey::OLD_INPUT => $this->flashBag->get(SessionKey::VALIDATION_DATA),
            ViewKey::FLASHES => $this->flashBag->all(),
            ViewKey::CSRF_TOKEN => $this->csrfTokenManager->getToken(),
        ];

        return $handler->handle(
            $request->withAttribute(RequestAttribute::VIEW_PAYLOAD, $viewContext)
        );
    }
}