<?php declare(strict_types=1);

namespace Concept\Core\Components\View;

use Concept\Core\Components\Telemetry\TelemetryCollector;
use Concept\Core\Components\Telemetry\TelemetryEvent;
use Concept\Core\Components\View\Contracts\ViewInterface;
use Twig\Environment as Twig;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class TwigView implements ViewInterface
{
    public function __construct(
        public readonly Twig $twig,
        private readonly string $defaultExtension,
        public readonly ?TelemetryCollector $telemetryCollector,
    ) {}

    /**
     * @param string $viewName
     * @param array<mixed> $data
     * @return string
     * @throws LoaderError
     * @throws RuntimeError
     * @throws SyntaxError
     */
    public function render(string $viewName, array $data = []): string
    {
        $telemetryId = '';
        try {
            $viewName = $this->ensureExtension($viewName);
            $telemetryId = $this->telemetryCollector?->start(TelemetryEvent::TPL_RENDERED, [
                'view' => $viewName,
            ]);

            return $this->twig->render($viewName, $data);
        } finally {
            $this->telemetryCollector?->finish(TelemetryEvent::TPL_RENDERED, (string)$telemetryId);
        }
    }

    public function share(mixed $data): void
    {
        foreach ($data as $key => $value) {
            $this->twig->addGlobal($key, $value);
        }
    }

    private function ensureExtension(string $viewName): string
    {
        if (str_ends_with($viewName, $this->defaultExtension)) {
            return $viewName;
        }

        return $viewName . $this->defaultExtension;
    }
}