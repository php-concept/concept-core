<?php declare(strict_types=1);

namespace Concept\Core\Http\Protocol;

/**
 * Standard HTTP headers
 */
class HttpHeader
{
    public const string CONTENT_TYPE = 'Content-Type';
    public const string ACCEPT = 'Accept';
    public const string HOST = 'Host';
    public const string REFERER = 'Referer';
    public const string X_CSRF_TOKEN = 'X-CSRF-TOKEN';
    public const string X_XSRF_TOKEN = 'X-XSRF-TOKEN';
    public const string X_REQUESTED_WITH = 'X-Requested-With';

    public const string AUTHORIZATION = 'Authorization';
}
