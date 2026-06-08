<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Locale\ConfigLocaleResolver;
use Concept\Core\Components\Locale\Contracts\LocaleResolverInterface;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Providers\LocaleServiceProvider;
use League\Container\Container;
use League\Container\ReflectionContainer;
use PHPUnit\Framework\TestCase;

final class LocaleServiceProviderTest extends TestCase
{
    public function testProvidesLocaleResolverInterface(): void
    {
        $provider = new LocaleServiceProvider();

        self::assertTrue($provider->provides(LocaleResolverInterface::class));
        self::assertFalse($provider->provides('unknown.service'));
    }

    public function testRegisterUsesConfigLocaleResolverByDefault(): void
    {
        $container = $this->makeContainer(null, 'uk');

        $provider = new LocaleServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        $resolver = $container->get(LocaleResolverInterface::class);

        self::assertInstanceOf(ConfigLocaleResolver::class, $resolver);
        self::assertSame('uk', $resolver->resolve());
    }

    public function testRegisterResolvesConfiguredLocaleResolver(): void
    {
        $container = $this->makeContainer(StubLocaleResolver::class, 'uk');

        $provider = new LocaleServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        $resolver = $container->get(LocaleResolverInterface::class);

        self::assertInstanceOf(StubLocaleResolver::class, $resolver);
        self::assertSame('de', $resolver->resolve());
    }

    /**
     * @param class-string<LocaleResolverInterface>|null $localeResolver
     */
    private function makeContainer(?string $localeResolver, string $locale): Container
    {
        $container = new Container();
        $container->delegate(new ReflectionContainer());

        $container->add(ConfigInterface::class, new class ($localeResolver, $locale) implements ConfigInterface {
            public function __construct(
                private readonly ?string $localeResolver,
                private readonly string $locale,
            ) {}

            public function get(string $key, mixed $default = null): mixed
            {
                if ($key === ConfigKey::APP_LOCALE_RESOLVER) {
                    return $this->localeResolver;
                }

                return $default;
            }

            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }

            public function getString(string $key, string $default = ''): string
            {
                return $key === ConfigKey::APP_LOCALE ? $this->locale : $default;
            }

            public function getInt(string $key, int $default = 0): int { return $default; }
            public function getBool(string $key, bool $default = false): bool { return $default; }
        })->setShared(true);

        return $container;
    }
}

final class StubLocaleResolver implements LocaleResolverInterface
{
    public function resolve(): string
    {
        return 'de';
    }
}
