<?php declare(strict_types=1);

namespace Concept\Core\Services\View;

use Concept\Core\Services\View\Contracts\ViewInterface;
use Concept\Core\Services\View\Contracts\ViewResponseFactoryInterface;
use Concept\Core\Http\Protocol\HttpHeader;
use Concept\Core\Http\Protocol\HttpStatusCode;
use Concept\Core\Http\Protocol\HttpValue;
use Concept\Core\Http\RequestAttribute;
use Concept\Core\Http\Contracts\ResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ViewResponseFactory implements ViewResponseFactoryInterface
{
    public function __construct(
        private readonly ServerRequestInterface $request,
        private readonly ResponseFactoryInterface $responseFactory,
        private readonly ViewInterface $view
    ) {}

    /**
     * @param string $template
     * @param array<string, mixed> $data
     * @param int $code
     * @return ResponseInterface
     */
    public function create(
        string $template,
        array $data = [],
        int $code = HttpStatusCode::OK
    ): ResponseInterface {
        $sharedData = $this->request->getAttribute(RequestAttribute::VIEW_PAYLOAD, []);
        if (is_array($sharedData)) {
            $this->view->share($sharedData);
        }

        $content = $this->view->render($template, $data);
        $response = $this->responseFactory->createResponse($code);
        $response->getBody()->write($content);

        return $response->withHeader(HttpHeader::CONTENT_TYPE, HttpValue::HTML);
    }
}
