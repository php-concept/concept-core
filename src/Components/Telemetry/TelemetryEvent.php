<?php declare(strict_types=1);

namespace Concept\Core\Components\Telemetry;

/**
 * String identifiers for {@see \League\Event\HasEventName} and listener registration.
 */
final class TelemetryEvent
{
    public const string FRAMEWORK_SERVICE_AWAKENING = 'framework.service_awakening';
    public const string FRAMEWORK_COMPONENT_REGISTERED = 'framework.component_registered';
    public const string HTTP_ROUTE_CALLABLE_INVOKE = 'http.route_callable_invoke';
    public const string HTTP_FORM_REQUEST_VALIDATED = 'http.form_request_validated';
    public const string DB_QUERY_EXECUTED = 'db.query_executed';
    public const string TPL_RENDERED = 'tpl.rendered';

    /**
     * @return list<string>
     */
    public static function telemetryEvents(): array
    {
        return [
            self::FRAMEWORK_SERVICE_AWAKENING,
            self::FRAMEWORK_COMPONENT_REGISTERED,
            self::HTTP_ROUTE_CALLABLE_INVOKE,
            self::HTTP_FORM_REQUEST_VALIDATED,
            self::DB_QUERY_EXECUTED,
            self::TPL_RENDERED,
        ];
    }
}
