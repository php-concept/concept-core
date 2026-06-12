<?php declare(strict_types=1);

namespace Tests\Core\Services\Locale;

use Concept\Core\Services\Config\Contracts\ConfigInterface;
use Concept\Core\Services\Locale\ConfigLocaleResolver;
use Concept\Core\Foundation\ConfigKey;
use PHPUnit\Framework\TestCase;

final class ConfigLocaleResolverTest extends TestCase
{
    public function testResolveReturnsConfiguredLocale(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::once())->method('getString')->with(ConfigKey::APP_LOCALE, 'en')->willReturn('uk');

        $resolver = new ConfigLocaleResolver($config);

        self::assertSame('uk', $resolver->resolve());
    }

    public function testResolveFallsBackToEnglish(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::once())->method('getString')->with(ConfigKey::APP_LOCALE, 'en')->willReturn('en');

        $resolver = new ConfigLocaleResolver($config);

        self::assertSame('en', $resolver->resolve());
    }
}
