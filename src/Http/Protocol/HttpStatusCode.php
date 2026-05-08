<?php declare(strict_types=1);

namespace Concept\Core\Http\Protocol;

/**
 * Registry of standard HTTP response status codes.
 */
class HttpStatusCode
{
    // --- 2xx Success ---

    /** Standard response for successful HTTP requests */
    public const int OK = 200;

    /** The request has been fulfilled, resulting in the creation of a new resource */
    public const int CREATED = 201;

    /** The request has been accepted for processing, but the processing has not been completed */
    public const int ACCEPTED = 202;

    /** The server successfully processed the request and is not returning any content */
    public const int NO_CONTENT = 204;


    // --- 3xx Redirection ---

    /** This and all future requests should be directed to the given URI */
    public const int MOVED_PERMANENTLY = 301;

    /** Common redirect: the resource resides temporarily under a different URI */
    public const int FOUND = 302;

    /** Indicates that the resource has not been modified since the last request */
    public const int NOT_MODIFIED = 304;

    /** The request should be repeated with another URI; however, future requests should still use the original URI */
    public const int TEMPORARY_REDIRECT = 307;

    /** The request and all future requests should be repeated using another URI */
    public const int PERMANENT_REDIRECT = 308;


    // --- 4xx Client Errors ---

    /** The server cannot or will not process the request due to a clear client error */
    public const int BAD_REQUEST = 400;

    /** Similar to 403 Forbidden, but specifically for use when authentication is required */
    public const int UNAUTHORIZED = 401;

    /** The request was valid, but the server is refusing action. User might not have necessary permissions */
    public const int FORBIDDEN = 403;

    /** The requested resource could not be found but may be available in the future */
    public const int NOT_FOUND = 404;

    /** A request method is not supported for the requested resource */
    public const int METHOD_NOT_ALLOWED = 405;

    /** Indicates that the request could not be processed because of conflict in the current state of the resource */
    public const int CONFLICT = 409;

    /** The request is larger than the server is willing or able to process */
    public const int PAYLOAD_TOO_LARGE = 413;

    /** Custom code: The session has expired or CSRF token is invalid */
    public const int PAGE_EXPIRED = 419;

    /** The request was well-formed but was unable to be followed due to semantic errors (Validation failed) */
    public const int UNPROCESSABLE_ENTITY = 422;

    /** The user has sent too many requests in a given amount of time */
    public const int TOO_MANY_REQUESTS = 429;


    // --- 5xx Server Errors ---

    /** A generic error message, given when an unexpected condition was encountered */
    public const int INTERNAL_SERVER_ERROR = 500;

    /** The server either does not recognize the request method, or it lacks the ability to fulfil the request */
    public const int NOT_IMPLEMENTED = 501;

    /** The server was acting as a gateway or proxy and received an invalid response from the upstream server */
    public const int BAD_GATEWAY = 502;

    /** The server cannot handle the request (because it is overloaded or down for maintenance) */
    public const int SERVICE_UNAVAILABLE = 503;

    /** The server was acting as a gateway or proxy and did not receive a timely response from the upstream server */
    public const int GATEWAY_TIMEOUT = 504;

    public static function getReasonPhrase(int $code): string
    {
        return match ($code) {
            self::OK => 'OK',
            self::CREATED => 'Created',
            self::NO_CONTENT => 'No Content',

            self::MOVED_PERMANENTLY => 'Moved Permanently',
            self::FOUND => 'Found',

            self::BAD_REQUEST => 'Bad Request',
            self::UNAUTHORIZED => 'Unauthorized',
            self::FORBIDDEN => 'Forbidden',
            self::NOT_FOUND => 'Not Found',
            self::METHOD_NOT_ALLOWED => 'Method Not Allowed',
            self::PAGE_EXPIRED => 'Page Expired',
            self::UNPROCESSABLE_ENTITY => 'Unprocessable Entity',

            self::INTERNAL_SERVER_ERROR => 'Internal Server Error',
            self::NOT_IMPLEMENTED => 'Not Implemented',
            self::SERVICE_UNAVAILABLE => 'Service Unavailable',

            default => 'Error',
        };
    }
}