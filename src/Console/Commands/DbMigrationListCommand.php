<?php declare(strict_types=1);

namespace Concept\Core\Console\Commands;

use Illuminate\Database\Capsule\Manager as CapsuleManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DbMigrationListCommand extends Command
{
    private const string COMMAND_NAME = 'migration:list';
    private const string COMMAND_DESCRIPTION = 'Show migrations list';
    private const string OPTION_LIMIT = 'limit';
    private const string OPTION_LIMIT_SHORTCUT = 'l';
    private const string OPTION_LIMIT_DESCRIPTION = 'The count of migrations to display';
    private const int DEFAULT_LIMIT = 10;
    private const string MSG_MIGRATIONS_LIST = 'Migrations List';
    private const string MSG_NOT_FOUND = 'No migrations found.';
    private const string MSG_TOTAL = 'Showing top %d migrations.';

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION)
            ->addOption(
                self::OPTION_LIMIT,
                self::OPTION_LIMIT_SHORTCUT,
                InputOption::VALUE_OPTIONAL,
                self::OPTION_LIMIT_DESCRIPTION,
                self::DEFAULT_LIMIT
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $limit = $input->getOption(self::OPTION_LIMIT);
        if (is_numeric($limit)) {
            $limit = (int)$limit;
        } else {
            $limit = self::DEFAULT_LIMIT;
        }

        $io->title(self::MSG_MIGRATIONS_LIST);
        /** @var array<array<string, mixed>> $migrations */
        $migrations = CapsuleManager::table('migrations')
            ->limit($limit)
            ->get()
            ->map(fn($item) => (array) $item)
            ->toArray();

        if (empty($migrations)) {
            $io->warning(self::MSG_NOT_FOUND);

            return Command::SUCCESS;
        }

        $header = array_keys($migrations[0]);
        $io->table($header, $migrations);
        $io->success(sprintf(self::MSG_TOTAL, $limit));

        return Command::SUCCESS;
    }
}