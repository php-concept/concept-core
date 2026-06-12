<?php declare(strict_types=1);

namespace Concept\Core\Services\Caster\Contracts;

use Concept\Core\Services\Caster\Exceptions\CastingException;

/**
 * Contract for transforming raw data into specific types or objects (DTOs).
 */
interface CasterInterface
{
    /**
     * Cast raw value to the specified type.
     *
     * @param mixed $value Raw input data
     * @param string $type Target type (scalar or class name)
     * @return mixed Transformed data
     * @throws CastingException
     */
    public function cast(mixed $value, string $type): mixed;
}