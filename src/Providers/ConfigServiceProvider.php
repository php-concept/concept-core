<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Config\Config;
use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Foundation\PathManager;
use Concept\Core\Foundation\PathName;
use Concept\Core\Telemetry\TelemetryTrait;
use Dotenv\Dotenv;
use League\Container\ServiceProvider\AbstractServiceProvider;
use League\Container\ServiceProvider\BootableServiceProviderInterface;
use Noodlehaus\Config as nhConfig;

class ConfigServiceProvider extends AbstractServiceProvider implements BootableServiceProviderInterface
{
    use TelemetryTrait;

    private const string APP_ENV_KEY = 'APP_ENV';

    public function provides(string $id): bool
    {
        $services = [
            ConfigInterface::class,
        ];

        return in_array($id, $services);
    }

    public function register(): void
    {
    }

    public function boot(): void
    {
        $container = $this->getContainer();

        /** @var PathManager $pathManager */
        $pathManager = $container->get(PathManager::class);

        $nhConfig = new nhConfig($pathManager->get(PathName::CONFIG));
        $envData = $this->loadDotEnv($pathManager->root());
        $this->loadOverrideConfig($nhConfig, $envData, $pathManager);

        $this->mergeEnvData($nhConfig, $envData);
        $config = new Config($nhConfig);
        $this->setTimeZone($config->getString(ConfigKey::APP_TIMEZONE, 'UTC'));

        $container->add(ConfigInterface::class, $config)->setShared(true);
    }

    /**
     * @param string $rootPath
     * @return array<string, string|null>
     */
    private function loadDotEnv(string $rootPath): array
    {
        $dotenv = Dotenv::createImmutable($rootPath);

        return $dotenv->load();
    }

    /**
     * @param nhConfig $nhConfig
     * @param array<string, string|null> $envData
     * @param PathManager $pathManager
     */
    private function loadOverrideConfig(nhConfig $nhConfig, array $envData, PathManager $pathManager): void
    {
        $env = $envData[self::APP_ENV_KEY] ?? '';
        $overrideConfigPath = $pathManager->get(PathName::CONFIG, $env);
        if (is_dir($overrideConfigPath)) {
            $overrideConfig = new nhConfig($overrideConfigPath);
            $nhConfig->merge($overrideConfig);
        }
    }

    /**
     * @param nhConfig $nhConfig
     * @param array<string, mixed> $envData
     * @return void
     */
    private function mergeEnvData(nhConfig $nhConfig, array $envData): void
    {
        foreach ($envData as $key => $value) {
            $parts = explode('_', strtolower($key), 2);
            $root = $parts[0];
            $sub  = $parts[1] ?? '';

            $configKey = empty($sub) ? $root : sprintf('%s.%s', $root, $sub);
            $nhConfig->set($configKey, $value);
        }
    }

    private function setTimeZone(string $timeZone): void
    {
        date_default_timezone_set($timeZone);
    }
}
