<?php declare(strict_types=1);

namespace Tests\Core\Components\Validator;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Locale\ConfigLocaleResolver;
use Concept\Core\Components\Locale\Contracts\LocaleResolverInterface;
use Concept\Core\Foundation\PathManager;
use Concept\Core\Foundation\PathName;
use Concept\Core\Components\Validator\ValidationTranslationsLoader;
use PHPUnit\Framework\TestCase;

final class ValidationTranslationsLoaderTest extends TestCase
{
    private string $tmpDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpDir = sys_get_temp_dir() . '/validation-translations-' . bin2hex(random_bytes(6));
        mkdir($this->tmpDir, 0777, true);
    }

    protected function tearDown(): void
    {
        if (is_dir($this->tmpDir)) {
            $this->removeDirectory($this->tmpDir);
        }

        parent::tearDown();
    }

    private function removeDirectory(string $dir): void
    {
        foreach (scandir($dir) ?: [] as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }

            $path = $dir . '/' . $entry;
            if (is_dir($path)) {
                $this->removeDirectory($path);
            } else {
                unlink($path);
            }
        }

        rmdir($dir);
    }

    public function testLoadReturnsEmptyForMissingFile(): void
    {
        $loader = $this->makeLoader();

        self::assertSame(
            ['messages' => [], 'translations' => [], 'aliases' => []],
            $loader->load($this->tmpDir . '/missing.php'),
        );
    }

    public function testLoadAndMergeFiles(): void
    {
        $baseFile = $this->tmpDir . '/base.php';
        $overrideFile = $this->tmpDir . '/override.php';

        file_put_contents($baseFile, <<<'PHP'
<?php return [
    'messages' => ['required' => 'Required'],
    'translations' => ['or' => 'or'],
];
PHP);

        file_put_contents($overrideFile, <<<'PHP'
<?php return [
    'messages' => ['required' => 'Обов\'язкове', 'email' => 'Email'],
    'translations' => ['or' => 'або'],
    'aliases' => ['email' => 'Email-адреса', 'password' => 'Пароль'],
];
PHP);

        $loader = $this->makeLoader();
        $merged = $loader->mergeFiles([$baseFile, $overrideFile]);

        self::assertSame('Обов\'язкове', $merged['messages']['required']);
        self::assertSame('Email', $merged['messages']['email']);
        self::assertSame('або', $merged['translations']['or']);
        self::assertSame('Email-адреса', $merged['aliases']['email']);
        self::assertSame('Пароль', $merged['aliases']['password']);
    }

    public function testLoadForLocaleFallsBackToConfiguredLocale(): void
    {
        $translationsDir = $this->tmpDir . '/validator';
        mkdir($translationsDir, 0777, true);
        file_put_contents($translationsDir . '/en.php', <<<'PHP'
<?php return [
    'messages' => ['required' => 'Required'],
    'translations' => [],
    'aliases' => [],
];
PHP);

        $config = $this->createStub(ConfigInterface::class);
        $config->method('getString')->willReturnMap([
            ['app.locale', 'en', 'uk'],
            ['app.fallback_locale', 'en', 'en'],
        ]);

        $loader = $this->makeLoader(
            new ConfigLocaleResolver($config),
            new PathManager($this->tmpDir, [
                PathName::VALIDATOR_TRANSLATIONS => 'validator',
            ]),
            $config,
        );

        self::assertSame('Required', $loader->loadForLocale()['messages']['required']);
        self::assertSame('Required', $loader->resolve()['messages']['required']);
    }

    public function testLoadForLocaleUsesCustomFallbackLocaleFromConfig(): void
    {
        $translationsDir = $this->tmpDir . '/validator';
        mkdir($translationsDir, 0777, true);
        file_put_contents($translationsDir . '/de.php', <<<'PHP'
<?php return [
    'messages' => ['required' => 'Erforderlich'],
    'translations' => [],
    'aliases' => [],
];
PHP);

        $config = $this->createStub(ConfigInterface::class);
        $config->method('getString')->willReturnMap([
            ['app.locale', 'en', 'uk'],
            ['app.fallback_locale', 'en', 'de'],
        ]);

        $loader = $this->makeLoader(
            localeResolver: $this->createStub(LocaleResolverInterface::class),
            paths: new PathManager($this->tmpDir, [
                PathName::VALIDATOR_TRANSLATIONS => 'validator',
            ]),
            config: $config,
        );

        self::assertSame('Erforderlich', $loader->loadForLocale()['messages']['required']);
    }

    public function testResolveReturnsEmptyWhenValidatorTranslationsPathIsNotMapped(): void
    {
        $loader = $this->makeLoader();

        self::assertSame(
            ['messages' => [], 'translations' => [], 'aliases' => []],
            $loader->resolve(),
        );
    }

    private function makeLoader(
        ?LocaleResolverInterface $localeResolver = null,
        ?PathManager $paths = null,
        ?ConfigInterface $config = null,
    ): ValidationTranslationsLoader {
        $localeResolver ??= $this->createStub(LocaleResolverInterface::class);
        $paths ??= new PathManager($this->tmpDir, []);
        $config ??= $this->createStub(ConfigInterface::class);

        return new ValidationTranslationsLoader($localeResolver, $paths, $config);
    }
}
