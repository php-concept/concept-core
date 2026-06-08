<?php declare(strict_types=1);

namespace Concept\Core\Components\View;

use Concept\Core\Telemetry\TelemetryCollector;
use Concept\Core\Telemetry\TelemetryEvent;
use Concept\Core\Components\View\Contracts\ViewInterface;
use League\Plates\Engine;

class PlatesView implements ViewInterface
{
    public function __construct(
        public readonly Engine $engine,
        public readonly ?TelemetryCollector $telemetryCollector,
    ) {}

    /**
     * @param string $viewName
     * @param array<mixed> $data
     * @return string
     */
    public function render(string $viewName, array $data = []): string
    {
        $telemetryId = '';
        try {
            $telemetryId = $this->telemetryCollector?->start(TelemetryEvent::TPL_RENDERED, [
                'view' => $viewName,
            ]);

            return $this->engine->render($viewName, $data);
        } finally {
            $this->telemetryCollector?->finish(TelemetryEvent::TPL_RENDERED, (string)$telemetryId);
        }
    }

    public function share(mixed $data): void
    {
        $this->engine->addData($data);
    }
}
