<?php declare(strict_types=1);

namespace Concept\Core\Http\Protocol;

/**
 * Standard HTTP methods
 */
class HttpMethod
{
    public const string GET = 'GET';
    public const string POST = 'POST';
    public const string PUT = 'PUT';
    public const string DELETE = 'DELETE';
    public const string PATCH = 'PATCH';
    public const string HEAD = 'HEAD';
    public const string OPTIONS = 'OPTIONS';
}