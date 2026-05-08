<?php declare(strict_types=1);

namespace Concept\Core\Components\Caster\Exceptions;

use Exception;
use Throwable;

/**
 * Exception thrown when the Caster fails to map data to a specific type.
 */
class CastingException extends Exception
{
    private const string ERR_MESSAGE_FORMAT = 'Failed to cast provided data to type: %s';

    public function __construct(string $targetType, ?Throwable $previous = null)
    {
        $message = sprintf(self::ERR_MESSAGE_FORMAT, $targetType);
        if ($previous) {
            $message = sprintf('%s. Reason: %s', $message, $previous->getMessage());
        }

        parent::__construct($message, 0, $previous);
    }
}