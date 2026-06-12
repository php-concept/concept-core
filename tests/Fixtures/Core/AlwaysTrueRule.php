<?php declare(strict_types=1);

namespace Tests\Fixtures\Core;

use Concept\Core\Services\Validator\Rule;

final class AlwaysTrueRule extends Rule
{
    public function passes($value): bool
    {
        return true;
    }
}
