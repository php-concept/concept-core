<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Path\PathManager;
use Concept\Core\Components\Config\Config;
use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Dotenv\Dotenv;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Noodlehaus\Config as nhConfig;

class ConfigServiceProvider extends AbstractServiceProvider
{
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
        $container = $this->getContainer();
        $container->add(ConfigInterface::class, function () use ($container) {
            /** @var PathManager $pathManager */
            $pathManager = $container->get(PathManager::class);

            $nhConfig = new nhConfig($pathManager->get(PathManager::CONFIG_DIR));
            $envData = $this->loadDotEnv($pathManager->root());
            $this->loadOverrideConfig($nhConfig, $envData, $pathManager);

            $config = new Config($nhConfig);
            $this->mergeEnvData($config, $envData);
            $this->setTimeZone($config->getString('app.timezone', 'UTC'));

            return $config;
        })->setShared(true);
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
        $overrideConfigPath = $pathManager->get(PathManager::CONFIG_DIR, $env);
        if (is_dir($overrideConfigPath)) {
            $overrideConfig = new nhConfig($overrideConfigPath);
            $nhConfig->merge($overrideConfig);
        }
    }

    /**
     * @param ConfigInterface $config
     * @param array<string, mixed> $envData
     * @return void
     */
    private function mergeEnvData(ConfigInterface $config, array $envData): void
    {
        foreach ($envData as $key => $value) {
            $parts = explode('_', strtolower($key), 2);
            $root = $parts[0];
            $sub  = $parts[1] ?? '';

            $configKey = empty($sub) ? $root : sprintf('%s.%s', $root, $sub);
            $config->set($configKey, $value);
        }
    }

    private function setTimeZone(string $timeZone): void
    {
        date_default_timezone_set($timeZone);
    }
}
