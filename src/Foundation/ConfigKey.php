<?php declare(strict_types=1);

namespace Concept\Core\Foundation;

/**
 * Canonical configuration keys used by the framework core.
 */
final class ConfigKey
{
    public const string APP_DEBUG = 'app.debug';

    public const string APP_NAME = 'app.name';

    public const string APP_VERSION = 'app.version';

    public const string APP_TIMEZONE = 'app.timezone';

    public const string APP_LOCALE = 'app.locale';

    public const string APP_FALLBACK_LOCALE = 'app.fallback_locale';

    public const string APP_LOCALE_RESOLVER = 'app.locale_resolver';

    public const string COMMANDS = 'commands';

    public const string COMPONENTS = 'components';

    public const string ROUTES = 'routes';

    public const string ROUTES_LIST = 'routes.list';

    public const string ROUTES_INTERCEPTORS = 'routes.interceptors';

    public const string DB_DRIVER = 'db.driver';

    public const string DB_HOST = 'db.host';

    public const string DB_DATABASE = 'db.database';

    public const string DB_USERNAME = 'db.username';

    public const string DB_PASSWORD = 'db.password';

    public const string DB_CHARSET = 'db.charset';

    public const string LOG_NAME = 'log.name';

    public const string LOG_LEVEL = 'log.level';

    public const string LOG_MAX_FILES = 'log.max_files';

    public const string LOG_QUERY = 'log.query';

    public const string LOG_VALIDATION_DATA = 'log.validation_data';

    public const string MASKING_PATTERNS = 'masking.patterns';

    public const string MASKING_KEY_PATTERNS = 'masking.key_patterns';

    public const string MASKING_RULES = 'masking.rules';

    public const string MIGRATIONS_TABLE = 'migrations.table';

    public const string MIGRATIONS_PATHS = 'migrations.paths';

    public const string SEEDERS_LIST = 'seeders.list';

    public const string SESSION_COOKIE_LIFETIME = 'session.cookie_lifetime';

    public const string SESSION_COOKIE_PATH = 'session.cookie_path';

    public const string SESSION_COOKIE_SECURE = 'session.cookie_secure';

    public const string SESSION_COOKIE_HTTPONLY = 'session.cookie_httponly';

    public const string SESSION_USE_ONLY_COOKIES = 'session.use_only_cookies';

    public const string SESSION_COOKIE_DOMAIN = 'session.cookie_domain';

    public const string SESSION_COOKIE_SAMESITE = 'session.cookie_samesite';

    public const string SESSION_USE_STRICT_MODE = 'session.use_strict_mode';

    public const string TELEMETRY_ENABLED = 'telemetry.enabled';

    public const string VALIDATOR_RULES = 'validator.rules';

    public const string VIEW_CACHE_DIR = 'view.cache_dir';

    public const string VIEW_DEFAULT_EXTENSION = 'view.default_extension';

    public const string VIEW_PATHS = 'view.paths';

    public const string VIEW_EXTENSIONS = 'view.extensions';

    public const string VIEW_CONTEXTS = 'view.contexts';
}
