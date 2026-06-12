<?php declare(strict_types=1);

namespace Concept\Core\Services\Locale\Contracts;

interface LocaleResolverInterface
{
    public function resolve(): string;
}
