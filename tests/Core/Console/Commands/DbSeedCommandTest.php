<?php declare(strict_types=1);

namespace Tests\Core\Console\Commands;

use Concept\Core\Components\Path\PathManager;
use Concept\Core\Console\Commands\DbSeedCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class DbSeedCommandTest extends TestCase
{
    private string $tmpRoot;

    protected function setUp(): void
    {
        parent::setUp();
        $this->tmpRoot = sys_get_temp_dir() . '/seed-command-' . bin2hex(random_bytes(6));
        mkdir($this->tmpRoot . '/database/seeders', 0777, true);
    }

    protected function tearDown(): void
    {
        $marker = $this->tmpRoot . '/seed-ran.txt';
        $seeder = $this->tmpRoot . '/database/seeders/DatabaseSeeder.php';

        if (is_file($marker)) {
            unlink($marker);
        }
        if (is_file($seeder)) {
            unlink($seeder);
        }
        if (is_dir($this->tmpRoot . '/database/seeders')) {
            rmdir($this->tmpRoot . '/database/seeders');
        }
        if (is_dir($this->tmpRoot . '/database')) {
            rmdir($this->tmpRoot . '/database');
        }
        if (is_dir($this->tmpRoot)) {
            rmdir($this->tmpRoot);
        }

        parent::tearDown();
    }

    public function testExecuteRunsSeederAndPrintsSuccess(): void
    {
        $marker = $this->tmpRoot . '/seed-ran.txt';
        $seederFile = $this->tmpRoot . '/database/seeders/DatabaseSeeder.php';

        file_put_contents($seederFile, "<?php return new class { public function run(): void { file_put_contents('" . addslashes($marker) . "', 'ok'); } }; ");

        $pathManager = new PathManager($this->tmpRoot, [
            PathManager::SEEDERS_DIR => 'database/seeders',
        ]);

        $tester = new CommandTester(new DbSeedCommand($pathManager));

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertFileExists($marker);
        self::assertStringContainsString('Database seeded successfully.', $tester->getDisplay());
    }

    public function testExecuteReturnsFailureWhenSeederThrows(): void
    {
        $seederFile = $this->tmpRoot . '/database/seeders/DatabaseSeeder.php';
        file_put_contents($seederFile, "<?php return new class { public function run(): void { throw new \\RuntimeException('seeding failed'); } }; ");

        $pathManager = new PathManager($this->tmpRoot, [
            PathManager::SEEDERS_DIR => 'database/seeders',
        ]);

        $tester = new CommandTester(new DbSeedCommand($pathManager));

        $exitCode = $tester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('seeding failed', $tester->getDisplay());
    }
}
