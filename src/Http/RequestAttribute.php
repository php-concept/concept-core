<?php declare(strict_types=1);

namespace Concept\Core\Http;

/**
 * Keys used to store attributes in the PSR-7 ServerRequestInterface
 * Used for sharing data between middlewares and controllers
 */
class RequestAttribute
{
    /** For safe back url (stored in StorePreviousUrlMiddleware) */
    public const string SAFE_BACK_URL = 'safe_back_url';

    /** For sharing data with the template engine (used in ShareTemplateDataMiddleware) */
    public const string VIEW_CONTEXT = 'view_context';
}