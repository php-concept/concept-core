<?php declare(strict_types=1);

namespace Concept\Core\Components\View\Twig;

use Concept\Core\Events\View\TemplateProfileEntry;
use League\Event\EventDispatcher;
use Twig\Profiler\Profile;

/**
 * Maps a finished Twig {@see Profile} node to a telemetry event.
 */
final class TwigProfilerRecorder
{
    public static function dispatchProfile(?EventDispatcher $events, Profile $profile, int $depth = 0): void
    {
        if ($events === null || $profile->isRoot()) {
            return;
        }

        $startTime = $profile->getStartTime();
        $endTime = $profile->getEndTime();
        $duration = $profile->getDuration();

        if ($startTime > 0.0 && $endTime > 0.0) {
            $duration = max(0.0, $endTime - $startTime);
        }

        $events->dispatch(new TemplateProfileEntry(
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

    /**
     * @deprecated Prefer {@see TelemetryProfilerExtension} incremental dispatch during render.
     *
     * @return list<array{
     *     template: string,
     *     type: string,
     *     name: string,
     *     duration_seconds: float,
     *     memory_bytes: int,
     *     start_time: float,
     *     end_time: float,
     *     depth: int
     * }>
     */
    public static function flatten(Profile $profile, int $depth = 0): array
    {
        $entries = [];

        if (!$profile->isRoot()) {
            $startTime = $profile->getStartTime();
            $endTime = $profile->getEndTime();
            $duration = $profile->getDuration();

            if ($startTime > 0.0 && $endTime > 0.0) {
                $duration = max(0.0, $endTime - $startTime);
            }

            $entries[] = [
                'template' => $profile->getTemplate(),
                'type' => $profile->getType(),
                'name' => $profile->getName(),
                'duration_seconds' => $duration,
                'memory_bytes' => $profile->getMemoryUsage(),
                'start_time' => $startTime,
                'end_time' => $endTime,
                'depth' => $depth,
            ];
        }

        foreach ($profile as $child) {
            if ($child instanceof Profile) {
                $entries = array_merge($entries, self::flatten($child, $depth + 1));
            }
        }

        return $entries;
    }
}
