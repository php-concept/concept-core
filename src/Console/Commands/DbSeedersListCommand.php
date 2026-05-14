<?php declare(strict_types=1);

namespace Concept\Core\Console\Commands;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class DbSeedersListCommand extends Command
{
    private const string COMMAND_NAME = 'seeders:list';
    private const string COMMAND_DESCRIPTION = 'Show seeders list';
    private const string MSG_MIGRATIONS_LIST = 'Seeders List';
    private const string MSG_NOT_FOUND = 'No seeders found.';
    private const string MSG_END_OF_LIST = 'End of seeders list.';

    public function __construct(
        private readonly ConfigInterface $config,
    ) {
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

        /** @var array<string> $seedersList */
        $seedersList = $this->config->get('seeders.list', []);
        if (empty($seedersList)) {
            $io->warning(self::MSG_NOT_FOUND);

            return Command::SUCCESS;
        }

        $io->title(self::MSG_MIGRATIONS_LIST);
        foreach ($seedersList as $seeder) {
            $io->writeln($seeder);
        }

        $io->success(self::MSG_END_OF_LIST);

        return Command::SUCCESS;
    }
}