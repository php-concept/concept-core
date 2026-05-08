<?php declare(strict_types=1);

namespace Tests\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Providers\DatabaseServiceProvider;
use Illuminate\Database\Capsule\Manager as CapsuleManager;
use League\Container\Container;
use PHPUnit\Framework\TestCase;

final class DatabaseServiceProviderAdvancedTest extends TestCase
{
    /**
     * Verifies that the database configuration is correctly mapped to Eloquent's connection options.
     */
    public function testBootCorrectlyMapsDatabaseConfiguration(): void
    {
        $container = new Container();
        $config = $this->createStub(ConfigInterface::class);
        
        $configData = [
            'db.driver' => 'pgsql',
            'db.host' => '10.10.10.10',
            'db.database' => 'test_db',
            'db.username' => 'test_user',
            'db.password' => 'test_pass',
            'db.charset' => 'utf8',
        ];

        $config->method('getString')->willReturnCallback(function (string $key, string $default = '') use ($configData) {
            return $configData[$key] ?? $default;
        });
        
        $config->method('getBool')->willReturn(false); // disable logging

        $container->add(ConfigInterface::class, $config);

        $provider = new DatabaseServiceProvider();
        $provider->setContainer($container);
        $provider->boot();

        /** @var CapsuleManager $capsule */
        $capsule = $container->get(CapsuleManager::class);
        $connectionConfig = $capsule->getConnection()->getConfig();

        self::assertSame('pgsql', $connectionConfig['driver']);
        self::assertSame('10.10.10.10', $connectionConfig['host']);
        self::assertSame('test_db', $connectionConfig['database']);
        self::assertSame('test_user', $connectionConfig['username']);
        self::assertSame('test_pass', $connectionConfig['password']);
        self::assertSame('utf8', $connectionConfig['charset']);
        self::assertSame('utf8mb4_unicode_ci', $connectionConfig['collation']); // hardcoded in provider
    }
}
