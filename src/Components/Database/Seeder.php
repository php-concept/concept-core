<?php declare(strict_types=1);

namespace Concept\Core\Components\Database;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Illuminate\Database\Seeder as IlluminateSeeder;
use Psr\Container\ContainerInterface;

class Seeder extends IlluminateSeeder
{
    public function __construct(
        private readonly ContainerInterface $appContainer,
        private readonly ConfigInterface $config,
    ) {
    }

    public function run(): void
    {
        $seedersList = $this->config->get('seeders.list', []);

        if (!empty($seedersList)) {
            foreach ($seedersList as $seederClass) {
                $this->resolveAndRun($seederClass);
            }
        }
    }

    private function resolveAndRun(string $class): void
    {
        $seeder = $this->appContainer->get($class);

        $seeder->run();
    }
}