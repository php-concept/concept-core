<?php declare(strict_types=1);

namespace Concept\Core\Services\Database\Contracts;

interface SeederInterface
{
    public function run(): void;
}