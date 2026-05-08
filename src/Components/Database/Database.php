<?php declare(strict_types=1);

namespace Concept\Core\Components\Database;

use Concept\Core\Components\Database\Contracts\DatabaseInterface;
use Illuminate\Database\Capsule\Manager as CapsuleManager;

class Database implements DatabaseInterface
{
    public function __construct(
        private readonly CapsuleManager $capsule
    ) {}

    public function capsule(): CapsuleManager
    {
        return $this->capsule;
    }
}