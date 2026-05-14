<?php declare(strict_types=1);

namespace Concept\Core\Components\Database\Contracts;

interface SeederInterface
{
    public function run(): void;
}