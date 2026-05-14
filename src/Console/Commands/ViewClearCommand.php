<?php declare(strict_types=1);

namespace Concept\Core\Console\Commands;

use Concept\Core\Components\Path\PathManager;
use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Illuminate\Filesystem\Filesystem;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Throwable;

class ViewClearCommand extends Command
{
    private const string COMMAND_NAME = 'view:clear';
    private const string COMMAND_DESCRIPTION = 'Clear all compiled view templates';

    private const string MSG_STARTING = 'Clearing view cache...';
    private const string MSG_SUCCESS = 'View cache cleared successfully.';
    private const string ERR_CLEAR_FAILED = 'Failed to clear view cache: %s';

    /**
     * @param PathManager $pathManager
     * @param Filesystem $filesystem
     * @param ConfigInterface $config
     */
    public function __construct(
        private readonly PathManager $pathManager,
        private readonly Filesystem $filesystem,
        private readonly ConfigInterface $config
    ) {
        parent::__construct();
    }

    /**
     * {@inheritdoc}
     */
    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION);
    }

    /**
     * {@inheritdoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(self::MSG_STARTING);

        try {
            $cacheSubDir = $this->config->getString('twig.cache_dir', 'views');
            $cachePath = $this->pathManager->get(PathManager::CACHE_DIR, $cacheSubDir);

            if ($this->filesystem->exists($cachePath)) {
                $this->filesystem->cleanDirectory($cachePath);
            }

            $io->success(self::MSG_SUCCESS);

            return Command::SUCCESS;
        } catch (Throwable $exception) {
            $io->error(sprintf(self::ERR_CLEAR_FAILED, $exception->getMessage()));

            return Command::FAILURE;
        }
    }
}
