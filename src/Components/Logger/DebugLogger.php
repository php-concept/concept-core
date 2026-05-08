<?php declare(strict_types=1);

namespace Concept\Core\Components\Logger;

use Concept\Core\Components\Path\PathManager;
use DateTimeImmutable;

class DebugLogger
{
    private const string DATE_TIME_FORMAT = 'Y-m-d H:i:s.u';
    private const string DEBUG_FILE_NAME = 'debug.log';

    private static ?self $instance = null;

    public function __construct(private readonly PathManager $pathManager)
    {
    }

    public static function setInstance(self $instance): void
    {
        self::$instance = $instance;
    }

    public static function log(mixed ...$data): void
    {
        if (self::$instance === null) {
            return;
        }

        foreach ($data as $item) {
            self::$instance->write($item);
        }
    }

    private function write(mixed $data): void
    {
        $logFile = $this->pathManager->get(PathManager::LOGS_DIR, self::DEBUG_FILE_NAME);

        $now = new DateTimeImmutable();
        $timestamp = $now->format(self::DATE_TIME_FORMAT);

        $output = is_scalar($data)
            ? (string)$data
            : json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);

        $entry = sprintf("[%s] %s\n", $timestamp, $output);

        file_put_contents($logFile, $entry, FILE_APPEND | LOCK_EX);
    }
}