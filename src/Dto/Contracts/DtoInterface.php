<?php declare(strict_types=1);

namespace Concept\Core\Dto\Contracts;

interface DtoInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array;
}