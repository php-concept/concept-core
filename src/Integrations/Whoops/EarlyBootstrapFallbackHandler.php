<?php declare(strict_types=1);

namespace Concept\Core\Integrations\Whoops;

use Concept\Core\Http\Protocol\HttpStatusCode;
use Whoops\Handler\Handler;

final class EarlyBootstrapFallbackHandler extends Handler
{
    private const string FALLBACK_FILE_PATH = '%s/resources/views/errors/fallback/500.php';

    public function __construct(private readonly string $rootPath) {}

    public function handle(): int
    {
        if (!headers_sent()) {
            http_response_code(HttpStatusCode::INTERNAL_SERVER_ERROR);
        }

        $fallbackFile = sprintf(self::FALLBACK_FILE_PATH, $this->rootPath);
        if (file_exists($fallbackFile)) {
            include $fallbackFile;
        }

        return Handler::QUIT;
    }
}
