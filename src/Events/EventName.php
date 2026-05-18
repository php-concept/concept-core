<?php declare(strict_types=1);

namespace Concept\Core\Events;

/**
 * String identifiers for {@see \League\Event\HasEventName} and listener registration.
 */
final class EventName
{
    public const string HTTP_ROUTER_DISPATCH_STARTED = 'http.router_dispatch_started';
    public const string HTTP_ROUTER_DISPATCH_FINISHED = 'http.router_dispatch_finished';
    public const string HTTP_ROUTE_CALLABLE_INVOKING = 'http.route_callable_invoking';
    public const string HTTP_ROUTE_CALLABLE_INVOKED = 'http.route_callable_invoked';
    public const string HTTP_FORM_REQUEST_VALIDATING = 'http.form_request_validating';
    public const string HTTP_FORM_REQUEST_VALIDATED = 'http.form_request_validated';
    public const string HTTP_FORM_REQUEST_VALIDATION_FAILED = 'http.form_request_validation_failed';
    public const string VIEW_TEMPLATE_RENDERING = 'view.template_rendering';
    public const string VIEW_TEMPLATE_RENDERED = 'view.template_rendered';
    public const string VIEW_TEMPLATE_PROFILE_ENTRY = 'view.template_profile_entry';
    public const string FRAMEWORK_COMPONENT_REGISTERING = 'framework.component_registering';
    public const string FRAMEWORK_SERVICE_AWAKENING = 'framework.service_awakening';

    /**
     * @return list<string>
     */
    public static function telemetryEvents(): array
    {
        return [
            self::HTTP_ROUTER_DISPATCH_STARTED,
            self::HTTP_ROUTER_DISPATCH_FINISHED,
            self::HTTP_ROUTE_CALLABLE_INVOKING,
            self::HTTP_ROUTE_CALLABLE_INVOKED,
            self::HTTP_FORM_REQUEST_VALIDATING,
            self::HTTP_FORM_REQUEST_VALIDATED,
            self::HTTP_FORM_REQUEST_VALIDATION_FAILED,
            self::VIEW_TEMPLATE_RENDERING,
            self::VIEW_TEMPLATE_RENDERED,
            self::VIEW_TEMPLATE_PROFILE_ENTRY,
            self::FRAMEWORK_COMPONENT_REGISTERING,
            self::FRAMEWORK_SERVICE_AWAKENING,
        ];
    }
}
