<?php declare(strict_types=1);

namespace Concept\Core\Components\Locale\Contracts;

interface LocaleResolverInterface
{
    public function resolve(): string;
}
