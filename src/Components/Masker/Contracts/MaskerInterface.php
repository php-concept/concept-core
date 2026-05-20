<?php declare(strict_types=1);

namespace Concept\Core\Components\Masker\Contracts;

interface MaskerInterface
{
    public function mask(mixed $data): mixed;
}