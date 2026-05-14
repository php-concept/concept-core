<?php declare(strict_types=1);

namespace Tests\Core\Console\Commands;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Console\Commands\DbRollbackCommand;
use Illuminate\Database\Migrations\Migrator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class DbRollbackCommandTest extends TestCase
{
    private const array MIGRATION_PATHS = ['/tmp/app/database/migrations'];

    public function testExecuteRollsBackMigrationsAndPrintsSuccess(): void
    {
        $migrator = $this->createMock(Migrator::class);
        $migrator->expects(self::once())
            ->method('rollback')
            ->with(self::MIGRATION_PATHS)
            ->willReturn(['2026_01_01_000000_create_users_table']);

        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::once())
            ->method('get')
            ->with('migrations.paths', [])
            ->willReturn(self::MIGRATION_PATHS);

        $tester = new CommandTester(new DbRollbackCommand($migrator, $config));

        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Rolling back database migrations', $display);
        self::assertStringContainsString('Rolled back: 2026_01_01_000000_create_users_table', $display);
        self::assertStringContainsString('Database rollback completed successfully.', $display);
    }

    public function testExecuteShowsNothingToRollbackMessage(): void
    {
        $migrator = $this->createStub(Migrator::class);
        $migrator->method('rollback')->willReturn([]);

        $config = $this->createStub(ConfigInterface::class);
        $config->method('get')->willReturn(self::MIGRATION_PATHS);

        $tester = new CommandTester(new DbRollbackCommand($migrator, $config));

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Nothing to rollback.', $tester->getDisplay());
    }

    public function testExecuteReturnsFailureOnException(): void
    {
        $migrator = $this->createStub(Migrator::class);
        $migrator->method('rollback')->willThrowException(new \RuntimeException('rollback fail'));

        $config = $this->createStub(ConfigInterface::class);
        $config->method('get')->willReturn(self::MIGRATION_PATHS);

        $tester = new CommandTester(new DbRollbackCommand($migrator, $config));

        $exitCode = $tester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Rollback failed: rollback fail', $tester->getDisplay());
    }
}
