<?php declare(strict_types=1);

namespace Concept\Core\Components\View;

use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Events\View\TemplateRendered;
use Concept\Core\Events\View\TemplateRendering;
use League\Event\EventDispatcher;
use Twig\Environment as Twig;
use Twig\Profiler\Profile;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class View implements ViewInterface
{
    public function __construct(
        public readonly Twig $twig,
        private readonly ?EventDispatcher $events = null,
        private readonly ?Profile $twigProfile = null,
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
        if (!str_ends_with($viewName, self::DEFAULT_EXTENSION)) {
            $viewName .= self::DEFAULT_EXTENSION;
        }

        if ($this->twigProfile !== null) {
            $this->twigProfile->reset();
        }

        $this->events?->dispatch(new TemplateRendering($viewName));

        $startedAt = microtime(true);

        try {
            return $this->twig->render($viewName, $data);
        } finally {
            $this->events?->dispatch(new TemplateRendered($viewName, microtime(true) - $startedAt));
        }
    }
}