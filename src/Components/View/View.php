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
        $viewName = $this->ensureExtension($viewName);

        $this->resetProfile();
        $this->events?->dispatch(new TemplateRendering($viewName));

        $startedAt = microtime(true);

        try {
            return $this->twig->render($viewName, $data);
        } finally {
            $this->handlePostRender($viewName, $startedAt);
        }
    }

    private function ensureExtension(string $viewName): string
    {
        if (str_ends_with($viewName, self::DEFAULT_EXTENSION)) {
            return $viewName;
        }

        return $viewName . self::DEFAULT_EXTENSION;
    }

    private function resetProfile(): void
    {
        $this->twigProfile?->reset();
    }

    private function handlePostRender(string $viewName, float $startedAt): void
    {
        if ($this->twigProfile !== null) {
            $this->dispatchProfileEntries($this->twigProfile);
        }

        $duration = microtime(true) - $startedAt;
        $this->events?->dispatch(new TemplateRendered($viewName, $duration));
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

        $this->events->dispatch(new TemplateProfileEntry(
            $profile->getTemplate(),
            $profile->getType(),
            $profile->getName(),
            $this->calculateDuration($profile),
            $profile->getMemoryUsage(),
            $profile->getStartTime(),
            $profile->getEndTime(),
            $depth,
        ));
    }

    private function calculateDuration(Profile $profile): float
    {
        $startTime = $profile->getStartTime();
        $endTime = $profile->getEndTime();

        if ($startTime > 0.0 && $endTime > 0.0) {
            return max(0.0, $endTime - $startTime);
        }

        return $profile->getDuration();
    }
}