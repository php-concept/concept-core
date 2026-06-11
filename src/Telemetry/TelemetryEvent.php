<?php declare(strict_types=1);

namespace Concept\Core\Telemetry;

/**
 * Telemetry event identifiers.
 */
final class TelemetryEvent
{
    public const string FRAMEWORK_SERVICE_AWAKENING = 'framework.service_awakening';
    public const string FRAMEWORK_COMPONENT_REGISTERED = 'framework.component_registered';
    public const string FRAMEWORK_ROUTES_REGISTERED = 'framework.routes_registered';
    public const string HTTP_ROUTE_INTERCEPTOR_EXECUTED = 'http.route_interceptor_executed';
    public const string HTTP_ROUTE_CALLABLE_INVOKED = 'http.route_callable_invoked';
    public const string HTTP_FORM_REQUEST_VALIDATED = 'http.form_request_validated';
    public const string HTTP_REQUEST_HANDLED = 'http.request_handled';
    public const string DB_QUERY_EXECUTED = 'db.query_executed';
    public const string TPL_RENDERED = 'tpl.rendered';
}
