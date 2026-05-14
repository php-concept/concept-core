<?php declare(strict_types=1);

namespace Concept\Core\Console\Commands;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Illuminate\Database\Migrations\Migrator;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class DbRollbackCommand extends Command
{
    private const string COMMAND_NAME = 'db:rollback';
    private const string COMMAND_DESCRIPTION = 'Rollback the last database migration batch';
    private const string MSG_STARTING = 'Rolling back database migrations...';
    private const string MSG_SUCCESS = 'Database rollback completed successfully.';
    private const string MSG_NOTHING = 'Nothing to rollback.';
    private const string MSG_ROLLED_BACK = ' <info>✔</info> Rolled back: <comment>%s</comment> ... ';
    private const string ERR_ROLLBACK_FAILED = 'Rollback failed: %s';

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
            /** @var array<string> $paths */
            $paths = $this->config->get('migrations.paths', []);
            $executed = $this->migrator->rollback($paths);

            if (empty($executed)) {
                $io->info(self::MSG_NOTHING);
                return Command::SUCCESS;
            }

            foreach ($executed as $migration) {
                $io->writeln(sprintf(self::MSG_ROLLED_BACK, $migration));
            }

            $io->newLine();
            $io->success(self::MSG_SUCCESS);

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $io->error(sprintf(self::ERR_ROLLBACK_FAILED, $exception->getMessage()));

            return Command::FAILURE;
        }
    }
}