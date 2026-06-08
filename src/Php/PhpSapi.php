<?php declare(strict_types=1);

namespace Concept\Core\Php;

/**
 * PHP SAPI identifiers returned by {@see PHP_SAPI}.
 *
 * @see https://www.php.net/manual/en/function.php-sapi-name.php
 */
class PhpSapi
{
    public const string CLI = 'cli';

    public const string CLI_SERVER = 'cli-server';

    public const string CGI_FCGI = 'cgi-fcgi';

    public const string FPM_FCGI = 'fpm-fcgi';

    public const string APACHE2HANDLER = 'apache2handler';

    public const string APACHE2FILTER = 'apache2filter';

    public const string LITESPEED = 'litespeed';

    public const string PHPDBG = 'phpdbg';

    public const string EMBED = 'embed';
}
