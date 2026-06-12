<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Services\Config\Contracts\ConfigInterface;
use Concept\Core\Services\DataMasker\Contracts\DataMaskerInterface;
use Concept\Core\Services\DataMasker\Contracts\DataMaskerRuleInterface;
use Concept\Core\Services\DataMasker\DataMasker;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Providers\Support\DataMaskerServiceProvider;
use League\Container\Container;
use League\Container\ReflectionContainer;
use PHPUnit\Framework\TestCase;

final class DataMaskerServiceProviderTest extends TestCase
{
    public function testProvidesDataMaskerInterface(): void
    {
        $provider = new DataMaskerServiceProvider();

        self::assertTrue($provider->provides(DataMaskerInterface::class));
        self::assertFalse($provider->provides('data_masker.unknown'));
    }

    public function testRegisterBuildsSharedDataMasker(): void
    {
        $container = new Container();
        $container->add(ConfigInterface::class, $this->makeConfig())->setShared(true);

        $provider = new DataMaskerServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        $first = $container->get(DataMaskerInterface::class);
        $second = $container->get(DataMaskerInterface::class);

        self::assertInstanceOf(DataMasker::class, $first);
        self::assertSame($first, $second);
    }

    public function testRegisterAppliesRegexPatternsFromConfig(): void
    {
        $container = new Container();
        $container->add(ConfigInterface::class, $this->makeConfig(
            keyPatterns: ['#.*password.*#i'],
            patterns: ['#secret@mail\.test#' => '***@***.***'],
        ))->setShared(true);

        $provider = new DataMaskerServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        /** @var DataMaskerInterface $masker */
        $masker = $container->get(DataMaskerInterface::class);

        $result = $masker->mask([
            'user_password' => 'secret123',
            'email' => 'secret@mail.test',
        ]);

        self::assertSame(DataMasker::MASK_CHARS, $result['user_password']);
        self::assertSame('***@***.***', $result['email']);
    }

    public function testRegisterLoadsAdditionalRulesFromContainer(): void
    {
        $container = new Container();
        $container->delegate(new ReflectionContainer());
        $container->add(ConfigInterface::class, $this->makeConfig(
            rules: [StubDataMaskerRule::class],
        ))->setShared(true);

        $provider = new DataMaskerServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        /** @var DataMaskerInterface $masker */
        $masker = $container->get(DataMaskerInterface::class);

        self::assertSame('top-*****-value', $masker->mask('top-secret-value'));
    }

    public function testRegisterThrowsWhenPatternsConfigIsNotArray(): void
    {
        $container = new Container();
        $container->add(ConfigInterface::class, $this->makeConfig(patterns: 'not-an-array'))->setShared(true);

        $provider = new DataMaskerServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        $this->expectException(\TypeError::class);

        $container->get(DataMaskerInterface::class);
    }

    /**
     * @param array<string, string> $patterns
     * @param list<string> $keyPatterns
     * @param list<class-string<DataMaskerRuleInterface>> $rules
     */
    private function makeConfig(
        array|string $patterns = [],
        array $keyPatterns = [],
        array $rules = [],
    ): ConfigInterface {
        return new class ($patterns, $keyPatterns, $rules) implements ConfigInterface {
            /**
             * @param array<string, string>|string $patterns
             * @param list<string> $keyPatterns
             * @param list<class-string<DataMaskerRuleInterface>> $rules
             */
            public function __construct(
                private readonly array|string $patterns,
                private readonly array $keyPatterns,
                private readonly array $rules,
            ) {}

            public function get(string $key, mixed $default = null): mixed
            {
                return match ($key) {
                    ConfigKey::MASKING_PATTERNS => $this->patterns,
                    ConfigKey::MASKING_KEY_PATTERNS => $this->keyPatterns,
                    ConfigKey::MASKING_RULES => $this->rules,
                    default => $default,
                };
            }

            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string { return $default; }
            public function getInt(string $key, int $default = 0): int { return $default; }
            public function getBool(string $key, bool $default = false): bool { return $default; }
        };
    }
}

final class StubDataMaskerRule implements DataMaskerRuleInterface
{
    public function isSensitiveKey(string $key): bool
    {
        return false;
    }

    public function apply(string $value): string
    {
        return str_replace('secret', '*****', $value);
    }
}
