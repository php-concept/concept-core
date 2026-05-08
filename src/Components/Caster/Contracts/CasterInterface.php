<?php declare(strict_types=1);

namespace Concept\Core\Components\Caster\Contracts;

use Concept\Core\Components\Caster\Exceptions\CastingException;

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