<?php declare(strict_types=1);

namespace Concept\Core\Components\Console;

use ReflectionClass;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class DisabledCommand extends Command
{
    private const string UNKNOWN_COMMAND_NAME = 'unknown';
    private const string TARGET_COMMAND_NAME_CONSTANT = 'COMMAND_NAME';
    private const string DESCRIPTION = '<fg=red>!!! WARNING: This command is disabled (possible missing dependencies). Run command to see details. !!!</>';
    private const string TITLE_PREFIX = 'Command Disabled: ';
    private const string WARNING_MISSING_DEPENDENCIES = 'This command cannot be executed because its dependencies are not met.';
    private const string WARNING_PROVIDER_NOT_REGISTERED = 'Likely a required ServiceProvider is not registered in bootstrap/providers/.';
    private const string LABEL_ORIGINAL_CLASS = 'Original Class';
    private const string LABEL_ERROR_DETAIL = 'Error Detail';

    public function __construct(
        private readonly string $fullClassName,
        private readonly string $errorMessage
    ) {
        $name = self::UNKNOWN_COMMAND_NAME;
        if (class_exists($this->fullClassName)) {
            $reflection = new ReflectionClass($this->fullClassName);
            $name = $reflection->getConstant(self::TARGET_COMMAND_NAME_CONSTANT) ?: self::UNKNOWN_COMMAND_NAME;
        }

        parent::__construct(is_scalar($name) ? (string)$name : self::UNKNOWN_COMMAND_NAME);

        $this->setDescription(self::DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title(self::TITLE_PREFIX . $this->getName());
        $io->warning([
            self::WARNING_MISSING_DEPENDENCIES,
            self::WARNING_PROVIDER_NOT_REGISTERED,
        ]);

        $io->definitionList(
            [self::LABEL_ORIGINAL_CLASS => $this->fullClassName],
            [self::LABEL_ERROR_DETAIL => $this->errorMessage]
        );

        return Command::FAILURE;
    }
}