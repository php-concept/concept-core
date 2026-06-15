<?php declare(strict_types=1);

namespace Concept\Core\Http\Middlewares;

use Concept\Core\Http\Requests\RequestAttribute;
use Concept\Core\Services\Session\SessionKey;
use Concept\Core\Services\View\ViewKey;
use Concept\Core\Http\Security\CsrfTokenManager;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\MiddlewareInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Concept\Core\Services\Session\Contracts\FlashBagInterface;

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