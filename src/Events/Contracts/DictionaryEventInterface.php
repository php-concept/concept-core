<?php declare(strict_types=1);

namespace Concept\Core\Events\Contracts;

/**
 * Events that contribute to a static registry/dictionary instead of a timeline span.
 */
interface DictionaryEventInterface
{
    /**
     * Type of dictionary (e.g. 'services', 'components', 'providers').
     */
    public function dictionaryType(): string;

    /**
     * The actual label or data for the dictionary entry.
     */
    public function dictionaryLabel(): string;

    /**
     * Optional detailed data for the dictionary entry.
     *
     * @return array<string, mixed>|null
     */
    public function dictionaryData(): ?array;
}
