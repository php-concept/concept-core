<?php declare(strict_types=1);

namespace Concept\Core\Dto;

use Concept\Core\Dto\Contracts\DtoInterface;

class Dto implements DtoInterface
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return (array)$this;
    }
}