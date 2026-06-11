<?php declare(strict_types=1);

namespace Concept\Core\Console\Commands;

use Concept\Core\Http\Routing\RouteDescriptor;
use League\Route\Route;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

final class RouteListCommand extends Command
{
    private const string COMMAND_NAME = 'route:list';
    private const string COMMAND_DESCRIPTION = 'List all registered application routes';
    private const string OPTION_FULL_MIDDLEWARE = 'full-middleware';
    private const string OPTION_FULL_MIDDLEWARE_SHORTCUT = 'F';
    private const string OPTION_FULL_MIDDLEWARE_DESCRIPTION = 'Display middleware with full class names';
    private const string MSG_TITLE = 'Application Routes';
    private const string MSG_NOT_FOUND = 'No routes found.';
    private const string MSG_TOTAL = 'Total: %d route(s).';

    public function __construct(private readonly RouteDescriptor $routeDescriptor)
    {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->setName(self::COMMAND_NAME)
            ->setDescription(self::COMMAND_DESCRIPTION)
            ->addOption(
                self::OPTION_FULL_MIDDLEWARE,
                self::OPTION_FULL_MIDDLEWARE_SHORTCUT,
                InputOption::VALUE_NONE,
                self::OPTION_FULL_MIDDLEWARE_DESCRIPTION
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title(self::MSG_TITLE);

        $routes = $this->routeDescriptor->all();
        if ($routes === []) {
            $io->warning(self::MSG_NOT_FOUND);

            return Command::SUCCESS;
        }

        usort(
            $routes,
            static fn(Route $left, Route $right): int => [$left->getPath(), $left->getMethod()] <=>
                [$right->getPath(), $right->getMethod()]
        );

        $fullMiddlewareClass = (bool) $input->getOption(self::OPTION_FULL_MIDDLEWARE);

        $io->table(
            ['Method', 'URI', 'Name', 'Action', 'Middleware'],
            array_map(
                function (Route $route) use ($fullMiddlewareClass): array {
                    $description = $this->routeDescriptor->describe($route, $fullMiddlewareClass);

                    return [
                        $description['method'],
                        $description['path'],
                        $description['name'] ?? '',
                        $description['action'],
                        implode(', ', $description['middleware']),
                    ];
                },
                $routes
            )
        );

        $io->success(sprintf(self::MSG_TOTAL, count($routes)));

        return Command::SUCCESS;
    }
}
