<?php declare(strict_types=1);

namespace Tests\Core\Console\Commands;

use Concept\Core\Services\Config\Contracts\ConfigInterface;
use Concept\Core\Console\Commands\DbMigrationListCommand;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class DbMigrationListCommandTest extends TestCase
{
    private Capsule $capsule;

    protected function setUp(): void
    {
        parent::setUp();

        $this->capsule = new Capsule();
        $this->capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $this->capsule->bootEloquent();

        $schema = $this->capsule->getConnection()->getSchemaBuilder();
        $schema->dropIfExists('migrations');
        $schema->create('migrations', function ($table): void {
            $table->increments('id');
            $table->string('migration');
            $table->integer('batch');
        });
    }

    public function testShowsWarningWhenNoMigrationsFound(): void
    {
        $tester = new CommandTester($this->createCommand());

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Migrations List', $tester->getDisplay());
        self::assertStringContainsString('No migrations found.', $tester->getDisplay());
    }

    public function testDisplaysRowsAndRespectsLimitOption(): void
    {
        $this->capsule->getConnection()->table('migrations')->insert([
            ['migration' => '2026_01_01_000000_create_users_table', 'batch' => 1],
            ['migration' => '2026_01_02_000000_create_posts_table', 'batch' => 1],
            ['migration' => '2026_01_03_000000_create_comments_table', 'batch' => 2],
        ]);

        $tester = new CommandTester($this->createCommand());

        $exitCode = $tester->execute(['--limit' => '2']);
        $display = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Migrations List', $display);
        self::assertStringContainsString('create_users_table', $display);
        self::assertStringContainsString('create_posts_table', $display);
        self::assertStringNotContainsString('create_comments_table', $display);
        self::assertStringContainsString('Showing top 2 migrations.', $display);
    }

    public function testFallsBackToDefaultLimitWhenInvalidOptionProvided(): void
    {
        for ($i = 1; $i <= 12; $i++) {
            $this->capsule->getConnection()->table('migrations')->insert([
                'migration' => sprintf('2026_01_%02d_000000_migration_%02d', $i, $i),
                'batch' => 1,
            ]);
        }

        $tester = new CommandTester($this->createCommand());

        $exitCode = $tester->execute(['--limit' => 'invalid']);
        $display = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Showing top 10 migrations.', $display);
        self::assertStringContainsString('migration_10', $display);
        self::assertStringNotContainsString('migration_11', $display);
        self::assertStringNotContainsString('migration_12', $display);
    }

    private function createCommand(): DbMigrationListCommand
    {
        $config = $this->createStub(ConfigInterface::class);
        $config->method('getString')
            ->willReturnCallback(static fn(string $key, string $default = ''): string => $default);

        return new DbMigrationListCommand($config, $this->capsule);
    }
}
