<?php declare(strict_types=1);

namespace Concept\Core\Services\Database;

use Concept\Core\Services\Database\Contracts\DatabaseInterface;
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