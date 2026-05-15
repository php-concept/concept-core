<?php declare(strict_types=1);

namespace Concept\Core\Console\Commands;

use Concept\Core\Components\Database\Registries\SeederRegistry;
use Concept\Core\Components\Database\SeederManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class DbSeedCommand extends Command
{
    private const string COMMAND_NAME = 'db:seed';
    private const string COMMAND_DESCRIPTION = 'Seed the database with records';
    private const string OPTION_CLASS = 'class';
    private const string OPTION_CLASS_SHORTCUT = 'c';
    private const string OPTION_CLASS_DESCRIPTION = 'The class name of the seeder';

    private const string MSG_STARTING = 'Starting database seeding...';
    private const string MSG_SEEDED = ' <info>✔</info> Seeded: <comment>%s</comment> ... ';
    private const string MSG_SUCCESS = 'Database seeding completed successfully.';

    public function __construct(private readonly SeederManager $seeder)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION)
            ->addOption(
                self::OPTION_CLASS,
                self::OPTION_CLASS_SHORTCUT,
                InputOption::VALUE_OPTIONAL,
                self::OPTION_CLASS_DESCRIPTION
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(self::MSG_STARTING);

        try {
            /** @var string $class */
            $class = $input->getOption(self::OPTION_CLASS);
            if ($class) {
                $this->seeder->resolveAndRun($class);
                $io->writeln(sprintf(self::MSG_SEEDED, $class));
                $io->success(self::MSG_SUCCESS);
            } else {
                $executed = $this->seeder->run();
                foreach ($executed as $seeder) {
                    $io->writeln(sprintf(self::MSG_SEEDED, $seeder));
                }
                $io->success(self::MSG_SUCCESS);
            }
            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}