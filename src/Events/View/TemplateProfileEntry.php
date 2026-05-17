<?php declare(strict_types=1);

namespace Concept\Core\Events\View;

use Concept\Core\Events\Contracts\DescribesTelemetryContext;
use Concept\Core\Events\EventName;
use League\Event\HasEventName;

final class TemplateProfileEntry implements HasEventName, DescribesTelemetryContext
{
    public function __construct(
        public readonly string $template,
        public readonly string $type,
        public readonly string $name,
        public readonly float $durationSeconds,
        public readonly int $memoryBytes,
        public readonly float $startTime,
        public readonly float $endTime,
        public readonly int $depth,
    ) {}

    public function eventName(): string
    {
        return EventName::VIEW_TEMPLATE_PROFILE_ENTRY;
    }

    public function context(): array
    {
        return [
            'template' => $this->template,
            'type' => $this->type,
            'name' => $this->name,
            'duration_seconds' => $this->durationSeconds,
            'memory_bytes' => $this->memoryBytes,
            'start_time' => $this->startTime,
            'end_time' => $this->endTime,
            'depth' => $this->depth,
        ];
    }

    /**
     * Human-readable label for DebugBar / timeline (not the event name).
     *
     * @param array<string, mixed> $context
     */
    public static function displayLabel(array $context): string
    {
        $type = (string) ($context['type'] ?? '');
        $template = (string) ($context['template'] ?? '');
        $name = (string) ($context['name'] ?? '');

        if ($type === 'template') {
            return $template !== '' ? $template : $name;
        }

        if ($type === '' || $name === '') {
            return $template;
        }

        return sprintf('%s:%s', $type, $name);
    }
}
