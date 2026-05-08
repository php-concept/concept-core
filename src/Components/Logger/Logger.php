<?php declare(strict_types=1);

namespace Concept\Core\Components\Logger;

use Concept\Core\Components\Logger\Contracts\LoggerInterface;
use Monolog\Level;
use Monolog\Logger as Monolog;
use Psr\Log\AbstractLogger;
use Stringable;
use Throwable;

class Logger extends AbstractLogger implements LoggerInterface
{
    public function __construct(private readonly Monolog $monolog) {}

    /**
     * @param Level $level
     * @param string|Stringable $message
     * @param array<mixed> $context
     * @return void
     */
    public function log($level, string|Stringable $message, array $context = []): void
    {
        $this->monolog->log($level, (string)$message, $context);
    }

    public function exception(Throwable $exception, string $uri = ''): void
    {
        $code = $exception->getCode();
        if (method_exists($exception, 'getStatusCode')) {
            $code = $exception->getStatusCode();
        }

        $context = [
            'code' => $code,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
            'trace' => $exception->getTraceAsString()
        ];
        if (!empty($uri)) {
            $context['uri'] = $uri;
        }

        $this->error($exception->getMessage(), $context);
    }
}