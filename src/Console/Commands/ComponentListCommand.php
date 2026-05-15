<?php declare(strict_types=1);

namespace Concept\Core\Console\Commands;

use Concept\Core\Components\Component\ComponentRegistry;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class ComponentListCommand extends Command
{
    private const string COMMAND_NAME = 'component:list';
    private const string COMMAND_DESCRIPTION = 'List registered application components';
    private const string MSG_TITLE = 'Application Components';
    private const string MSG_NOT_FOUND = 'No components are registered.';
    private const string MSG_TOTAL = 'Total: %d component(s).';

    public function __construct(private readonly ComponentRegistry $registry)
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
        $io->title(self::MSG_TITLE);

        $components = $this->registry->all();

        if ($components === []) {
            $io->warning(self::MSG_NOT_FOUND);

            return Command::SUCCESS;
        }

        $rows = [];
        foreach ($components as $component) {
            $rows[] = [
                $component->name(),
                $component->version(),
                $component::class,
                $component->description(),
            ];
        }

        $io->table(
            ['Name', 'Version', 'Class', 'Description'],
            $rows
        );

        $io->success(sprintf(self::MSG_TOTAL, count($components)));

        return Command::SUCCESS;
    }
}
