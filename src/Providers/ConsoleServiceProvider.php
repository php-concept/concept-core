<?php declare(strict_types=1);

namespace Concept\Core\Providers;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use League\Container\ServiceProvider\AbstractServiceProvider;
use Symfony\Component\Console\Application as ConsoleApplication;
use Symfony\Component\Console\Command\Command;

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
            $appName = $config->getString('app.name', self::DEFAULT_NAME);
            $appVersion = $config->getString('app.version', self::DEFAULT_VERSION);

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
        $commandClasses = $config->get('commands');
        foreach ($commandClasses as $className) {
            /** @var Command $commandInstance */
            $commandInstance = $container->get($className);
            $consoleApplication->addCommand($commandInstance);
        }
    }
}