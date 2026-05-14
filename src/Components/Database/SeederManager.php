<?php declare(strict_types=1);

namespace Concept\Core\Components\Database;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Components\Database\Contracts\SeederInterface;
use Illuminate\Database\Seeder as IlluminateSeeder;
use Psr\Container\ContainerInterface;

class SeederManager extends IlluminateSeeder
{
    public function __construct(
        private readonly ContainerInterface $appContainer,
        private readonly ConfigInterface $config,
    ) {}

    /**
     * @return array<string>
     */
    public function run(): array
    {
        /** @var array<string> $seedersList */
        $seedersList = $this->config->get('seeders.list', []);
        $completed = [];
        if (!empty($seedersList)) {
            foreach ($seedersList as $seederClass) {
                $this->resolveAndRun($seederClass);
                $completed[] = $seederClass;
            }
        }

        return $completed;
    }

    public function resolveAndRun(string $class): void
    {
        $seeder = $this->appContainer->get($class);
        if ($seeder instanceof SeederInterface) {
            $seeder->run();
        }
    }
}