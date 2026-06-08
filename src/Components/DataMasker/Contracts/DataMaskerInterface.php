<?php declare(strict_types=1);

namespace Concept\Core\Components\DataMasker\Contracts;

interface DataMaskerInterface
{
    public function mask(mixed $data): mixed;

    public function clearRules(): void;
}