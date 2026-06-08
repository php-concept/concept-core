<?php declare(strict_types=1);

namespace Concept\Core\Components\Locale;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Components\Locale\Contracts\LocaleResolverInterface;

final class ConfigLocaleResolver implements LocaleResolverInterface
{
    public function __construct(
        private readonly ConfigInterface $config,
    ) {}

    public function resolve(): string
    {
        return $this->config->getString(ConfigKey::APP_LOCALE, 'en');
    }
}
