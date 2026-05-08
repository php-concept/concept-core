<?php declare(strict_types=1);

namespace Concept\Core\Http;

/**
 * Central registry for all keys used in the View layer and Session.
 * Eliminates "magic strings" and provides a single source of truth.
 */
class ViewKey
{
    public const string AUTH_USER  = 'auth_user';
    public const string FLASHES    = 'flashes';
    public const string ERRORS     = 'errors';
    public const string OLD_INPUT  = 'old';
    public const string CSRF_TOKEN = 'csrf_token';
}