<?php declare(strict_types=1);

namespace Concept\Core\Providers\Console;

use Concept\Core\Services\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\ConfigKey;
use Concept\Core\Console\DisabledCommand;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;
use Throwable;

class ConsoleServiceProvider extends AbstractServiceProvider
{
    private const string DEFAULT_NAME = 'Console';
    private const string DEFAULT_VERSION = '1.0.0';

    public function provides(string $id): bool
    {
        return $id == ConsoleApplication::class;
    }

    /**
     * Register the Console Application and its commands
     */
    public function register(): void
    {
        $container = $this->getContainer();
        $container->add(ConsoleApplication::class, function () use ($container) {
            /** @var ConfigInterface $config */
            $config = $container->get(ConfigInterface::class);
            $appName = $config->getString(ConfigKey::APP_NAME, self::DEFAULT_NAME);
            $appVersion = $config->getString(ConfigKey::APP_VERSION, self::DEFAULT_VERSION);

            $consoleApplication = new ConsoleApplication($appName, $appVersion);
            $this->addConsoleCommands($consoleApplication);

            return $consoleApplication;
        })->setShared(true);
    }

    private function addConsoleCommands(ConsoleApplication $consoleApplication): void
    {
        $container = $this->getContainer();
        /** @var ConfigInterface $config */
        $config = $container->get(ConfigInterface::class);

        /** @var array<class-string> $commandClasses */
        $commandClasses = $config->get(ConfigKey::COMMANDS);
        foreach ($commandClasses as $className) {
            try {
                /** @var Command $commandInstance */
                $commandInstance = $container->get($className);
                $consoleApplication->addCommand($commandInstance);
            } catch (Throwable $e) {
                $consoleApplication->addCommand(new DisabledCommand($className, $e->getMessage()));
            }
        }
    }
}