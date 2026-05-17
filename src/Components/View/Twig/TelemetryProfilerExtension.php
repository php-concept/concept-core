<?php declare(strict_types=1);

namespace Concept\Core\Components\View\Twig;

use League\Event\EventDispatcher;
use Twig\Extension\ProfilerExtension;
use Twig\Profiler\Profile;

/**
 * Twig profiler that emits {@see \Concept\Core\Events\View\TemplateProfileEntry} on each
 * template/block/macro as soon as it finishes rendering (not after the full tree).
 */
final class TelemetryProfilerExtension extends ProfilerExtension
{
    public function __construct(
        Profile $profile,
        private readonly ?EventDispatcher $events,
    ) {
        parent::__construct($profile);
    }

    public function leave(Profile $profile): void
    {
        $depth = max(0, $this->activeDepth() - 2);

        parent::leave($profile);

        TwigProfilerRecorder::dispatchProfile($this->events, $profile, $depth);
    }

    private function activeDepth(): int
    {
        $reflection = new \ReflectionProperty(ProfilerExtension::class, 'actives');
        $reflection->setAccessible(true);

        /** @var list<Profile> $actives */
        $actives = $reflection->getValue($this);

        return \count($actives);
    }
}
