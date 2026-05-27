<?php declare(strict_types=1);

namespace Concept\Core\Integrations\Whoops;

use Throwable;

trait WriteToErrorLog
{
    private function writeToErrorLog(Throwable $exception): void
    {
        $uri = '';
        if (isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
        }

        $context = json_encode([
            'code' => $exception->getCode(),
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString(),
            'uri' => $uri,
            'bootstrap' => true,
        ], JSON_UNESCAPED_SLASHES | JSON_THROW_ON_ERROR);

        $line = sprintf(
            "[%s] app.ERROR: %s %s\n",
            date('c'),
            $exception->getMessage(),
            $context
        );

        error_log(rtrim($line));
    }
}