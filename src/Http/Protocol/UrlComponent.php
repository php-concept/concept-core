<?php declare(strict_types=1);

namespace Concept\Core\Http\Protocol;

/**
 * Keys of URL components returned by parse_url().
 */
class UrlComponent
{
    public const string SCHEME = 'scheme';
    public const string HOST = 'host';
    public const string PORT = 'port';
    public const string USER = 'user';
    public const string PASS = 'pass';
    public const string PATH = 'path';
    public const string QUERY = 'query';
    public const string FRAGMENT = 'fragment';
}