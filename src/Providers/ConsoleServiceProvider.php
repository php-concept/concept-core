<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Path\PathManager;
use Concept\Core\Components\Config\Contracts\ConfigInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use ReflectionClass;
use RuntimeException;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\CommandLoader\ContainerCommandLoader;

class ConsoleServiceProvider extends AbstractServiceProvider
{
    private const string DEFAULT_NAME = 'Console';
    private const string DEFAULT_VERSION = '1.0.0';
    private const string COMMANDS_LIST = 'commands.php';
    private const string ERR_INVALID_COMMAND_CLASS = 'Command class must have #[AsCommand] attribute. Class: %s';

    public function provides(string $id): bool
    {
        return $id == Application::class;
    }

    /**
     * Register the Console Application and its commands
     */
    public function register(): void
    {
        $container = $this->getContainer();

        /** @var PathManager $pathManager */
        $pathManager = $container->get(PathManager::class);
        $bootstrapDir = $pathManager->get(PathManager::BOOTSTRAP_DIR);
        $commandClasses = require sprintf('%s/%s', $bootstrapDir, self::COMMANDS_LIST);

        $commandMap = [];

        foreach ($commandClasses as $className) {
            $reflection = new ReflectionClass($className);
            $attributes = $reflection->getAttributes(AsCommand::class);

            if (isset($attributes[0])) {
                /** @var AsCommand $asCommand */
                $asCommand = $attributes[0]->newInstance();
                $commandMap[$asCommand->name] = $className;
            } else {
                throw new RuntimeException(sprintf(self::ERR_INVALID_COMMAND_CLASS, $className));
            }
        }

        $container->add(Application::class, function () use ($container, $commandMap) {
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);
            $appName = $config->getString('app.name', self::DEFAULT_NAME);
            $appVersion = $config->getString('app.version', self::DEFAULT_VERSION);
            $app = new Application($appName, $appVersion);

            $app->setCommandLoader(new ContainerCommandLoader($container, $commandMap));

            return $app;
        })->setShared(true);
    }
}