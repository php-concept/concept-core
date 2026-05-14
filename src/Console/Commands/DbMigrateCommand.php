<?php declare(strict_types=1);

namespace Concept\Core\Console\Commands;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class DbMigrateCommand extends Command
{
    private const string COMMAND_NAME = 'db:migrate';
    private const string COMMAND_DESCRIPTION = 'Run all outstanding database migrations';
    private const string MSG_STARTING = 'Starting database migrations...';
    private const string MSG_SUCCESS = 'Database migrations completed successfully.';
    private const string MSG_NOTHING = 'Nothing to migrate. Your database is up to date.';
    private const string MSG_MIGRATED = ' <info>✔</info> Migrated: <comment>%s</comment> ... ';
    private const string ERR_MIGRATION_FAILED = 'Migration failed: %s';

    public function __construct(private readonly Migrator $migrator, private readonly ConfigInterface $config)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(self::MSG_STARTING);

        try {
            if(!$this->migrator->repositoryExists()) {
                $this->migrator->getRepository()->createRepository();
            }

            $paths = $this->config->get('migrations.paths');
            $executed = $this->migrator->run($paths);
            if (empty($executed)) {
                $io->info(self::MSG_NOTHING);
                return Command::SUCCESS;
            }

            foreach ($executed as $migration) {
                $io->writeln(sprintf(self::MSG_MIGRATED, $migration));
            }

            $io->newLine();
            $io->success(self::MSG_SUCCESS);

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $io->error(sprintf(self::ERR_MIGRATION_FAILED, $exception->getMessage()));

            return Command::FAILURE;
        }
    }
}