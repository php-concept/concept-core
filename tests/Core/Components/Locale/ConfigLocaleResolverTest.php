<?php declare(strict_types=1);

namespace Tests\Core\Components\Locale;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Locale\ConfigLocaleResolver;
use PHPUnit\Framework\TestCase;

final class ConfigLocaleResolverTest extends TestCase
{
    public function testResolveReturnsConfiguredLocale(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::once())->method('getString')->with('app.locale', 'en')->willReturn('uk');

        $resolver = new ConfigLocaleResolver($config);

        self::assertSame('uk', $resolver->resolve());
    }

    public function testResolveFallsBackToEnglish(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::once())->method('getString')->with('app.locale', 'en')->willReturn('en');

        $resolver = new ConfigLocaleResolver($config);

        self::assertSame('en', $resolver->resolve());
    }
}
