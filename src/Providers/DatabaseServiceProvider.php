<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Database\Contracts\DatabaseInterface;
use Concept\Core\Components\Database\Database;
use Concept\Core\Components\Database\Registries\MigrationRegistry;
use Concept\Core\Components\Database\Registries\SeederRegistry;
use Concept\Core\Components\Database\SeederManager;
use Concept\Core\Components\Logger\Contracts\LoggerInterface;
use Concept\Core\Components\Telemetry\TelemetryEvent;
use Concept\Core\Components\Telemetry\TelemetryTrait;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Database\Capsule\Manager as CapsuleManager;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Psr\Container\ContainerInterface;

class DatabaseServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    use TelemetryTrait;

    private const string DEFAULT_TABLE_NAME = 'migrations';

    /**
     * Determine if the provider is deferred.
     */
    public function provides(string $id): bool
    {
        $services = [
            CapsuleManager::class,
            DatabaseInterface::class,
            Migrator::class,
            SeederManager::class,
            SeederRegistry::class,
            MigrationRegistry::class,
        ];

        return in_array($id, $services);
    }

    /**
     * Register the service provider.
     */
    public function register(): void
    {
        $container = $this->getContainer();

        $container->add(DatabaseInterface::class, function () use ($container) {
            $this->telemetry()?->mark(TelemetryEvent::FRAMEWORK_SERVICE_AWAKENING, DatabaseInterface::class);

            /** @var CapsuleManager $capsuleManager */
            $capsuleManager = $container->get(CapsuleManager::class);

            return new Database($capsuleManager);
        })->setShared(true);

        $container->add(Migrator::class, function () use ($container) {
            /** @var CapsuleManager $capsuleManager */
            $capsuleManager = $container->get(CapsuleManager::class);
            $manager = $capsuleManager->getDatabaseManager();
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            $migrationTableName = $config->getString('migrations.table', self::DEFAULT_TABLE_NAME);
            $repository = new DatabaseMigrationRepository($manager, $migrationTableName);

            return new Migrator($repository, $manager, new Filesystem());
        })->setShared(true);

        $container->add(SeederManager::class, function () use ($container) {
            /** @var SeederRegistry $seederRegistry */
            $seederRegistry = $container->get(SeederRegistry::class);

            return new SeederManager($container, $seederRegistry);
        })->setShared(true);

        $container->add(SeederRegistry::class, function () use ($container) {
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            /** @var array<string> $seeders */
            $seeders = $config->get('seeders.list', []);
            $seederRegistry = new SeederRegistry();
            $seederRegistry->append($seeders);

            return $seederRegistry;
        })->setShared(true);

        $container->add(MigrationRegistry::class, function () use ($container) {
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);

            /** @var array<string> $migrations */
            $migrations = $config->get('migrations.paths', []);
            $migrationRegistry = new MigrationRegistry();
            $migrationRegistry->append($migrations);

            return $migrationRegistry;
        })->setShared(true);
    }

    /**
     * Bootstrap the service provider.
     */
    public function boot(): void
    {
        $container = $this->getContainer();
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);

        $capsuleManager = new CapsuleManager();
        $connectionOptions = $this->getConnectionOptions($config);
        $capsuleManager->addConnection($connectionOptions);

        $capsuleManager->setAsGlobal();
        $capsuleManager->bootEloquent();
        $capsuleManager->setEventDispatcher(new Dispatcher(new IlluminateContainer()));

        $capsuleManager->getConnection()->listen(function (QueryExecuted $query) use ($container, $config) {
            $this->logQueries($container, $config, $query);
            $this->storeTelemetryData($config, $query);
        });

        $container->add(CapsuleManager::class, $capsuleManager);
    }

    /**
     * Get the connection options for the database connection.
     *
     * @param ConfigInterface $config
     * @return array<string, string>
     */
    private function getConnectionOptions(ConfigInterface $config): array
    {
        return [
            'driver' => $config->getString('db.driver', 'mysql'),
            'host' => $config->getString('db.host', '127.0.0.1'),
            'database' => $config->getString('db.database', 'db'),
            'username' => $config->getString('db.username', 'root'),
            'password' => $config->getString('db.password', ''),
            'charset' => $config->getString('db.charset', 'utf8mb4'),
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
        ];
    }

    private function logQueries(ContainerInterface $container, ConfigInterface $config, QueryExecuted $query): void
    {
        if (!$container->has(LoggerInterface::class)) {
            return;
        }

        if ($config->getBool('app.debug') || $config->getBool('log.query')) {
            /** @var LoggerInterface $logger */
            $logger = $container->get(LoggerInterface::class);
            $logger->debug('SQL: ' . $query->toRawSql(), [
                'sql' => $query->sql,
                'bindings' => $query->bindings,
                'time' => $query->time
            ]);
        }
    }

    private function storeTelemetryData(ConfigInterface $config, QueryExecuted $query): void
    {
        if (!$config->getBool('log.query')) {
            return;
        }

        $this->telemetry()?->start(TelemetryEvent::DB_QUERY_EXECUTED,
            [
                'sql' => $query->sql,
                'raw' => $query->toRawSql(),
                'bindings' => $query->bindings,
                'time' => $query->time,
                'connection' => $query->connectionName,
            ]
        );
    }
}
