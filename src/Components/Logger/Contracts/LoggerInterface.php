<?php declare(strict_types=1);

namespace Concept\Core\Components\Logger\Contracts;

use Psr\Log\LoggerInterface as PsrLoggerInterface;
use Throwable;

interface LoggerInterface extends PsrLoggerInterface
{
    public function exception(Throwable $exception, string $uri = ''): void;
}