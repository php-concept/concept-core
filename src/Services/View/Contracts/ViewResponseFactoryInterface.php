<?php declare(strict_types=1);

namespace Concept\Core\Services\View\Contracts;

use Concept\Core\Http\Protocol\HttpStatusCode;
use Psr\Http\Message\ResponseInterface;

interface ViewResponseFactoryInterface
{
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
    ): ResponseInterface;
}
