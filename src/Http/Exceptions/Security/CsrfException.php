<?php declare(strict_types=1);

namespace Concept\Core\Http\Exceptions\Security;

use Concept\Core\Http\Protocol\HttpStatusCode;
use Exception;

class CsrfException extends Exception
{
    private const string DEFAULT_MESSAGE = 'CSRF token mismatch';

    public function __construct(string $message = '')
    {
        parent::__construct($message ?: self::DEFAULT_MESSAGE, HttpStatusCode::PAGE_EXPIRED);
    }
}