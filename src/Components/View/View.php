<?php declare(strict_types=1);

namespace Concept\Core\Components\View;

use Concept\Core\Components\View\Contracts\ViewInterface;
use Concept\Core\Events\View\TemplateProfileEntry;
use Concept\Core\Events\View\TemplateRendered;
use Concept\Core\Events\View\TemplateRendering;
use Psr\EventDispatcher\EventDispatcherInterface;
use Twig\Environment as Twig;
use Twig\Profiler\Profile;
use Twig\Error\LoaderError;
use Twig\Error\RuntimeError;
use Twig\Error\SyntaxError;

class View implements ViewInterface
{
    public function __construct(
        public readonly Twig $twig,
        private readonly ?EventDispatcherInterface $events = null,
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
            if ($this->twigProfile !== null) {
                $this->dispatchProfileEntries($this->twigProfile);
            }

            $this->events?->dispatch(new TemplateRendered($viewName, microtime(true) - $startedAt));
        }
    }

    private function dispatchProfileEntries(Profile $profile, int $depth = 0): void
    {
        foreach ($profile as $child) {
            if (!$child instanceof Profile) {
                continue;
            }

            $this->dispatchProfileEntry($child, $depth);
            $this->dispatchProfileEntries($child, $depth + 1);
        }
    }

    private function dispatchProfileEntry(Profile $profile, int $depth): void
    {
        if ($this->events === null || $profile->isRoot()) {
            return;
        }

        $startTime = $profile->getStartTime();
        $endTime = $profile->getEndTime();
        $duration = $profile->getDuration();

        if ($startTime > 0.0 && $endTime > 0.0) {
            $duration = max(0.0, $endTime - $startTime);
        }

        $this->events->dispatch(new TemplateProfileEntry(
            $profile->getTemplate(),
            $profile->getType(),
            $profile->getName(),
            $duration,
            $profile->getMemoryUsage(),
            $startTime,
            $endTime,
            $depth,
        ));
    }
}