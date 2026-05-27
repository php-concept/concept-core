<?php declare(strict_types=1);

namespace Concept\Core\Integrations\Whoops;

use Whoops\Handler\Handler;

/**
 * Logs bootstrap failures before Monolog and Config are available.
 * Writes to PHP error_log.
 */
final class EarlyBootstrapErrorLogHandler extends Handler
{
    use WriteToErrorLog;

    public function handle(): int
    {
        $this->writeToErrorLog($this->getException());

        return Handler::DONE;
    }
}
