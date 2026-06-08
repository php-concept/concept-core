<?php declare(strict_types=1);

namespace Concept\Core\Components\Validator;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Locale\Contracts\LocaleResolverInterface;
use Concept\Core\Components\Path\PathManager;

class ValidationTranslationsLoader
{
    public function __construct(
        private readonly LocaleResolverInterface $localeResolver,
        private readonly PathManager $paths,
        private readonly ConfigInterface $config,
    ) {}

    /**
     * @return array{messages: array<string, string>, translations: array<string, string>, aliases: array<string, string>}
     */
    public function resolve(): array
    {
        if (!$this->paths->has(PathManager::VALIDATOR_TRANSLATIONS_DIR)) {
            return $this->emptyResult();
        }

        return $this->loadForLocale();
    }

    /**
     * @return array{messages: array<string, string>, translations: array<string, string>, aliases: array<string, string>}
     */
    public function load(string $file): array
    {
        if (!is_readable($file)) {
            return $this->emptyResult();
        }

        $data = require $file;
        if (!is_array($data)) {
            return $this->emptyResult();
        }

        return [
            'messages' => $this->stringMap($data['messages'] ?? []),
            'translations' => $this->stringMap($data['translations'] ?? []),
            'aliases' => $this->stringMap($data['aliases'] ?? []),
        ];
    }

    /**
     * @return array{messages: array<string, string>, translations: array<string, string>, aliases: array<string, string>}
     */
    public function loadForLocale(): array
    {
        $directory = $this->paths->get(PathManager::VALIDATOR_TRANSLATIONS_DIR);
        $locale = $this->localeResolver->resolve();
        $file = $directory . '/' . $locale . '.php';

        if (is_readable($file)) {
            return $this->load($file);
        }

        $fallbackLocale = $this->config->getString('app.fallback_locale', 'en');

        return $this->load($directory . '/' . $fallbackLocale . '.php');
    }

    /**
     * @param list<string> $files
     * @return array{messages: array<string, string>, translations: array<string, string>, aliases: array<string, string>}
     */
    public function mergeFiles(array $files): array
    {
        $messages = [];
        $translations = [];
        $aliases = [];

        foreach ($files as $file) {
            $loaded = $this->load($file);
            $messages = array_merge($messages, $loaded['messages']);
            $translations = array_merge($translations, $loaded['translations']);
            $aliases = array_merge($aliases, $loaded['aliases']);
        }

        return [
            'messages' => $messages,
            'translations' => $translations,
            'aliases' => $aliases,
        ];
    }

    /**
     * @return array{messages: array<string, string>, translations: array<string, string>, aliases: array<string, string>}
     */
    public function emptyResult(): array
    {
        return [
            'messages' => [],
            'translations' => [],
            'aliases' => [],
        ];
    }

    /**
     * @param mixed $values
     * @return array<string, string>
     */
    private function stringMap(mixed $values): array
    {
        if (!is_array($values)) {
            return [];
        }

        $map = [];
        foreach ($values as $key => $value) {
            if (is_string($key) && is_string($value)) {
                $map[$key] = $value;
            }
        }

        return $map;
    }
}
