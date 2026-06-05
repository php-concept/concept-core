<?php declare(strict_types=1);

namespace Concept\Core\Components\View;

use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Events\View\TemplateRendered;
use Concept\Core\Events\View\TemplateRendering;
use League\Plates\Engine;
use Psr\EventDispatcher\EventDispatcherInterface;

class PlatesView implements ViewInterface
{
    public function __construct(
        public readonly Engine $engine,
        private readonly ?EventDispatcherInterface $events = null,
    ) {}

    /**
     * @param string $viewName
     * @param array<mixed> $data
     * @return string
     */
    public function render(string $viewName, array $data = []): string
    {
        $this->events?->dispatch(new TemplateRendering($viewName));

        $startedAt = microtime(true);

        try {
            return $this->engine->render($viewName, $data);
        } finally {
            $duration = microtime(true) - $startedAt;
            $this->events?->dispatch(new TemplateRendered($viewName, $duration));
        }
    }
}
