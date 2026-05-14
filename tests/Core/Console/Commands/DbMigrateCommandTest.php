<?php declare(strict_types=1);

namespace Tests\Core\Console\Commands;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Console\Commands\DbMigrateCommand;
use Illuminate\Database\Migrations\MigrationRepositoryInterface;
use Illuminate\Database\Migrations\Migrator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class DbMigrateCommandTest extends TestCase
{
    private const array MIGRATION_PATHS = ['/tmp/app/database/migrations'];

    public function testExecuteRunsMigrationsAndPrintsSuccess(): void
    {
        $repo = $this->createMock(MigrationRepositoryInterface::class);
        $repo->expects(self::once())->method('createRepository');

        $migrator = $this->createMock(Migrator::class);
        $migrator->expects(self::once())->method('repositoryExists')->willReturn(false);
        $migrator->expects(self::once())->method('getRepository')->willReturn($repo);
        $migrator->expects(self::once())
            ->method('run')
            ->with(self::MIGRATION_PATHS)
            ->willReturn(['2026_01_01_000000_create_users_table']);

        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::once())
            ->method('get')
            ->with('migrations.paths')
            ->willReturn(self::MIGRATION_PATHS);

        $command = new DbMigrateCommand($migrator, $config);
        $tester = new CommandTester($command);

        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Starting database migrations', $display);
        self::assertStringContainsString('Migrated: 2026_01_01_000000_create_users_table', $display);
        self::assertStringContainsString('Database migrations completed successfully.', $display);
    }

    public function testExecuteShowsNothingToMigrateMessage(): void
    {
        $migrator = $this->createMock(Migrator::class);
        $migrator->method('repositoryExists')->willReturn(true);
        $migrator->expects(self::never())->method('getRepository');
        $migrator->expects(self::once())
            ->method('run')
            ->with(self::MIGRATION_PATHS)
            ->willReturn([]);

        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::once())
            ->method('get')
            ->with('migrations.paths')
            ->willReturn(self::MIGRATION_PATHS);

        $tester = new CommandTester(new DbMigrateCommand($migrator, $config));

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Nothing to migrate. Your database is up to date.', $tester->getDisplay());
    }

    public function testExecuteReturnsFailureOnException(): void
    {
        $migrator = $this->createStub(Migrator::class);
        $migrator->method('repositoryExists')->willReturn(true);
        $migrator->method('run')->willThrowException(new \RuntimeException('db down'));

        $config = $this->createStub(ConfigInterface::class);
        $config->method('get')->willReturn(self::MIGRATION_PATHS);

        $tester = new CommandTester(new DbMigrateCommand($migrator, $config));

        $exitCode = $tester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Migration failed: db down', $tester->getDisplay());
    }
}
