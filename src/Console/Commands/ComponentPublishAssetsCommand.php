<?php declare(strict_types=1);

namespace Concept\Core\Console\Commands;

use Concept\Core\Components\Component\ComponentRegistry;
use Concept\Core\Components\Path\PathManager;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Filesystem\Exception\IOExceptionInterface;
use Symfony\Component\Filesystem\Filesystem;

final class ComponentPublishAssetsCommand extends Command
{
    private const string COMMAND_NAME = 'component:publish-assets';
    private const string OPTION_OVERRIDE = 'override';
    private const string DESCRIPTION = 'Publish all component assets to public directory.';
    private const string MSG_NO_ASSETS = 'No assets configured to publish.';
    private const string MSG_SOURCE_NOT_FOUND = 'Source path not found: %s';
    private const string MSG_MIRRORED = 'Mirrored directory <info>%s</info> to <info>%s</info>';
    private const string MSG_COPIED = 'Copied file <info>%s</info> to <info>%s</info>';
    private const string MSG_ERROR_OCCURRED = 'An error occurred while copying assets: %s';
    private const string MSG_COMPLETED_WITH_ERRORS = 'Completed with %d errors.';
    private const string MSG_SUCCESS = 'All component assets published successfully.';

    public function __construct(
        private readonly PathManager $pathManager,
        private readonly ComponentRegistry $registry
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::DESCRIPTION);
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $filesystem = new Filesystem();

        $assets = $this->registry->assets();

        if (empty($assets)) {
            $io->warning(self::MSG_NO_ASSETS);
            return Command::SUCCESS;
        }

        $errors = 0;
        foreach ($assets as $source => $target) {
            $sourcePath = $this->pathManager->root($source);
            $targetPath = $this->pathManager->root($target);

            if (!$filesystem->exists($sourcePath)) {
                $io->error(sprintf(self::MSG_SOURCE_NOT_FOUND, $sourcePath));
                $errors++;
                continue;
            }

            try {
                if (is_dir($sourcePath)) {
                    $filesystem->mirror($sourcePath, $targetPath, null, [self::OPTION_OVERRIDE => true]);
                    $io->writeln(sprintf(self::MSG_MIRRORED, $source, $target));
                } else {
                    $filesystem->copy($sourcePath, $targetPath, true);
                    $io->writeln(sprintf(self::MSG_COPIED, $source, $target));
                }
            } catch (IOExceptionInterface $exception) {
                $io->error(sprintf(self::MSG_ERROR_OCCURRED, $exception->getMessage()));
                $errors++;
            }
        }

        if ($errors > 0) {
            $io->warning(sprintf(self::MSG_COMPLETED_WITH_ERRORS, $errors));
            return Command::FAILURE;
        }

        $io->success(self::MSG_SUCCESS);
        return Command::SUCCESS;
    }
}
