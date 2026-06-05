<?php declare(strict_types=1);

namespace Concept\Core\Integrations\Whoops;

use Throwable;
use Whoops\Handler\Handler;

class PhpErrorLogHandler extends Handler
{
    public function handle(): int
    {
        try {
            $this->writeToErrorLog($this->getException());
        } catch (Throwable) {
        }

        return Handler::DONE;
    }

    private function writeToErrorLog(Throwable $exception): void
    {
        $uri = '';
        if (isset($_SERVER['REQUEST_URI']) && is_string($_SERVER['REQUEST_URI'])) {
            $uri = $_SERVER['REQUEST_URI'];
        }

        try {
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
        } catch (Throwable) {
            $line = sprintf(
                "[%s] app.ERROR: %s\n",
                date('c'),
                $exception->getMessage()
            );
        }

        error_log(rtrim($line));
    }
}
