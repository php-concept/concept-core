<?php declare(strict_types=1);

namespace Concept\Core\Console\Commands;

use Concept\Core\Components\Path\PathManager;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

#[AsCommand(name: 'db:seed', description: 'Seed the database with records')]
class DbSeedCommand extends Command
{
    private const string COMMAND_NAME = 'db:seed';
    private const string COMMAND_DESCRIPTION = 'Seed the database with records';
    private const string OPTION_CLASS = 'class';
    private const string OPTION_CLASS_SHORTCUT = 'c';
    private const string OPTION_CLASS_DESCRIPTION = 'The class name of the seeder';
   private const string FILE_DATABASE_SEEDER = 'DatabaseSeeder.php';

    public function __construct(private readonly PathManager $pathManager)
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
        $io->title('Running Database Seeders');

        try {
            $seeder = require $this->pathManager->get(PathManager::SEEDERS_DIR, self::FILE_DATABASE_SEEDER);
            $seeder->run();
            $io->success('Database seeded successfully.');

            return Command::SUCCESS;
        } catch (Throwable $e) {
            $io->error($e->getMessage());

            return Command::FAILURE;
        }
    }
}