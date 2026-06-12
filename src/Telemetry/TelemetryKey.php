<?php declare(strict_types=1);

namespace Concept\Core\Telemetry;

/**
 * Canonical context keys used in telemetry payloads.
 */
final class TelemetryKey
{
    public const string NAME = 'name';
    public const string ROUTE = 'route';
    public const string HANDLER = 'handler';
    public const string VIEW = 'view';
    public const string SQL = 'sql';
    public const string RAW = 'raw';
    public const string BINDINGS = 'bindings';
    public const string TIME = 'time';
    public const string CONNECTION = 'connection';
    public const string FILES = 'files';
    public const string COUNT = 'count';
    public const string METHOD = 'method';
    public const string PATH = 'path';
    public const string MEMORY_START = 'memory_start';
    public const string MEMORY_END = 'memory_end';
    public const string MEMORY_PEAK = 'memory_peak';
    public const string LEVEL = 'level';
    public const string MESSAGE = 'message';
    public const string CONTEXT = 'context';
}
