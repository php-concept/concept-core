<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Locale\Contracts\LocaleResolverInterface;
use Concept\Core\Foundation\PathManager;
use Concept\Core\Components\Validator\Contracts\ValidatorInterface;
use Concept\Core\Components\Validator\ValidationTranslationsLoader;
use Concept\Core\Components\Validator\Validator;
use Concept\Core\Providers\ValidationServiceProvider;
use League\Container\Container;
use League\Container\ReflectionContainer;
use PHPUnit\Framework\TestCase;
use Rakit\Validation\Validator as RakitValidator;

final class ValidationServiceProviderTest extends TestCase
{
    public function testProvidesExpectedServices(): void
    {
        $provider = new ValidationServiceProvider();

        self::assertTrue($provider->provides(RakitValidator::class));
        self::assertTrue($provider->provides(ValidatorInterface::class));
        self::assertTrue($provider->provides(ValidationTranslationsLoader::class));
        self::assertFalse($provider->provides('unknown.service'));
    }

    public function testRegisterBindsValidatorAndRakitAsShared(): void
    {
        $container = $this->makeContainer();

        $provider = new ValidationServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        $rakitA = $container->get(RakitValidator::class);
        $rakitB = $container->get(RakitValidator::class);
        self::assertInstanceOf(RakitValidator::class, $rakitA);
        self::assertSame($rakitA, $rakitB);

        $validator = $container->get(ValidatorInterface::class);
        self::assertInstanceOf(Validator::class, $validator);
    }

    public function testRegisterBindsValidationTranslationsLoaderAsShared(): void
    {
        $container = $this->makeContainer();

        $provider = new ValidationServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        $loaderA = $container->get(ValidationTranslationsLoader::class);
        $loaderB = $container->get(ValidationTranslationsLoader::class);

        self::assertInstanceOf(ValidationTranslationsLoader::class, $loaderA);
        self::assertSame($loaderA, $loaderB);
    }

    public function testRakitValidatorIsNotPreloadedWithTranslations(): void
    {
        $container = $this->makeContainer();

        $provider = new ValidationServiceProvider();
        $provider->setContainer($container);
        $provider->register();

        /** @var RakitValidator $rakit */
        $rakit = $container->get(RakitValidator::class);

        self::assertSame([], $rakit->getMessages());
    }

    private function makeContainer(): Container
    {
        $container = new Container();
        $container->delegate(new ReflectionContainer());

        $container->add(PathManager::class, new PathManager(sys_get_temp_dir(), []))->setShared(true);
        $container->add(LocaleResolverInterface::class, new class implements LocaleResolverInterface {
            public function resolve(): string
            {
                return 'en';
            }
        })->setShared(true);

        $container->add(ConfigInterface::class, new class implements ConfigInterface {
            public function get(string $key, mixed $default = null): mixed
            {
                return $key === 'validator.rules' ? [] : $default;
            }

            public function set(string $key, mixed $default = null): void {}
            public function has(string $key): bool { return false; }
            public function all(): array { return []; }
            public function getString(string $key, string $default = ''): string { return $default; }
            public function getInt(string $key, int $default = 0): int { return $default; }
            public function getBool(string $key, bool $default = false): bool { return $default; }
        })->setShared(true);

        return $container;
    }
}
