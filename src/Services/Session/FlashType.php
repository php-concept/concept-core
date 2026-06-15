<?php declare(strict_types=1);

namespace Concept\Core\Services\Session;

/**
 * Canonical flash message types.
 */
final class FlashType
{
    public const string ERROR = 'error';

    public const string INFO = 'info';

    public const string SUCCESS = 'success';

    public const string WARNING = 'warning';
}
