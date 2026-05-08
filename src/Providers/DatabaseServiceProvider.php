<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Database\Database;
use Concept\Core\Components\Database\Contracts\DatabaseInterface;
use Concept\Core\Components\Logger\Contracts\LoggerInterface;
use Illuminate\Database\Capsule\Manager as CapsuleManager;
use Illuminate\Container\Container as IlluminateContainer;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Migrations\DatabaseMigrationRepository;
use Illuminate\Database\Migrations\Migrator;
use Illuminate\Events\Dispatcher;
use Illuminate\Filesystem\Filesystem;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;

class DatabaseServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    /**
     * Determine if the provider is deferred.
     */
    public function provides(string $id): bool
    {
        $services = [
            CapsuleManager::class,
            DatabaseInterface::class,
            Migrator::class,
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
            /** @var CapsuleManager $capsuleManager */
            $capsuleManager = $container->get(CapsuleManager::class);

            return new Database($capsuleManager);
        })->setShared(true);

        $container->add(Migrator::class, function () use ($container) {
            /** @var CapsuleManager $capsuleManager */
            $capsuleManager = $container->get(CapsuleManager::class);
            $manager = $capsuleManager->getDatabaseManager();
            $repository = new DatabaseMigrationRepository($manager, 'migrations');

            return new Migrator($repository, $manager, new Filesystem());
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

        if ($config->getBool('log.query')) {
            $capsuleManager->getConnection()->listen(static function (QueryExecuted $query) use ($container) {
                /** @var LoggerInterface $logger */
                $logger = $container->get(LoggerInterface::class);
                $logger->debug('SQL: ' . $query->sql, [
                    'bindings' => $query->bindings,
                    'time'     => $query->time
                ]);
            });
        }

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
}
