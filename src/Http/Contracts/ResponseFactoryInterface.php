<?php declare(strict_types=1);

namespace Concept\Core\Http\Contracts;

use Psr\Http\Message\ResponseFactoryInterface as PsrResponseFactoryInterface;
use Psr\Http\Message\ResponseInterface;

interface ResponseFactoryInterface extends PsrResponseFactoryInterface
{
    public function json(
        mixed $data,
        int $code = 200,
        int $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    ): ResponseInterface;

    public function jsonSuccess(
        mixed $data = [],
        int $code = 200,
        int $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    ): ResponseInterface;

    /**
     * @param string $message
     * @param int $code
     * @param array<string, mixed> $errors
     * @param int $jsonFlags
     * @return ResponseInterface
     */
    public function jsonError(
        string $message,
        int $code = 500,
        array $errors = [],
        int $jsonFlags = JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR
    ): ResponseInterface;

    public function redirect(string $url, int $status = 302): ResponseInterface;

    /**
     * @param string $urlName
     * @param array<string, mixed> $parameters
     * @param int $status
     * @return ResponseInterface
     */
    public function redirectByName(string $urlName, array $parameters = [], int $status = 302): ResponseInterface;

    public function back(int $status = 302, string $fallback = '/'): ResponseInterface;
}
