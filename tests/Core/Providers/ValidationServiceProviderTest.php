<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Validator\Contracts\ValidatorInterface;
use Concept\Core\Components\Validator\Validator;
use Concept\Core\Providers\ValidationServiceProvider;
use League\Container\Container;
use PHPUnit\Framework\TestCase;
use Rakit\Validation\Validator as RakitValidator;

final class ValidationServiceProviderTest extends TestCase
{
    public function testProvidesExpectedServices(): void
    {
        $provider = new ValidationServiceProvider();

        self::assertTrue($provider->provides(RakitValidator::class));
        self::assertTrue($provider->provides(ValidatorInterface::class));
        self::assertFalse($provider->provides('unknown.service'));
    }

    public function testRegisterBindsValidatorAndRakitAsShared(): void
    {
        $container = new Container();
        $container->add('config', new class {
            /** @return array<string, string> */
            public function get(string $key, mixed $default = null): array
            {
                return [];
            }
        })->setShared(true);

        // alias to satisfy ConfigInterface dependency contract in factory
        $container->add(\Concept\Core\Components\Config\Contracts\ConfigInterface::class, fn () => $container->get('config'))->setShared(true);

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
}
