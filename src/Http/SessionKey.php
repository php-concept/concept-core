<?php declare(strict_types=1);

namespace Concept\Core\Http;

/**
 * Storage keys for Session and FlashBag.
 */
class SessionKey
{
    public const string CSRF_TOKEN = '_csrf_token';
    public const string VALIDATION_ERRORS = '_validation_errors';
    public const string VALIDATION_DATA = '_validation_data';
    public const string PREVIOUS_URL = '_url_previous';
    public const string CURRENT_URL = '_url_current';
}