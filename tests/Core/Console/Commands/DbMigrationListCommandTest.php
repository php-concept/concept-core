<?php declare(strict_types=1);

namespace Tests\Core\Console\Commands;

use Concept\Core\Console\Commands\DbMigrationListCommand;
use Illuminate\Database\Capsule\Manager as Capsule;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class DbMigrationListCommandTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        $capsule = new Capsule();
        $capsule->addConnection([
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
        $capsule->setAsGlobal();
        $capsule->bootEloquent();

        Capsule::schema()->dropIfExists('migrations');
        Capsule::schema()->create('migrations', function ($table): void {
            $table->increments('id');
            $table->string('migration');
            $table->integer('batch');
        });
    }

    public function testShowsWarningWhenNoMigrationsFound(): void
    {
        $tester = new CommandTester(new DbMigrationListCommand());

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Migrations List', $tester->getDisplay());
        self::assertStringContainsString('No migrations found.', $tester->getDisplay());
    }

    public function testDisplaysRowsAndRespectsLimitOption(): void
    {
        Capsule::table('migrations')->insert([
            ['migration' => '2026_01_01_000000_create_users_table', 'batch' => 1],
            ['migration' => '2026_01_02_000000_create_posts_table', 'batch' => 1],
            ['migration' => '2026_01_03_000000_create_comments_table', 'batch' => 2],
        ]);

        $tester = new CommandTester(new DbMigrationListCommand());

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
            Capsule::table('migrations')->insert([
                'migration' => sprintf('2026_01_%02d_000000_migration_%02d', $i, $i),
                'batch' => 1,
            ]);
        }

        $tester = new CommandTester(new DbMigrationListCommand());

        $exitCode = $tester->execute(['--limit' => 'invalid']);
        $display = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Showing top 10 migrations.', $display);
        self::assertStringContainsString('migration_10', $display);
        self::assertStringNotContainsString('migration_11', $display);
        self::assertStringNotContainsString('migration_12', $display);
    }
}
