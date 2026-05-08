<?php declare(strict_types=1);

namespace Concept\Core\Components\Database\Contracts;

use Illuminate\Database\Capsule\Manager as CapsuleManager;

interface DatabaseInterface
{
    public function capsule(): CapsuleManager;
}