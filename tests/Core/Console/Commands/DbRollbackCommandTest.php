<?php declare(strict_types=1);

namespace Tests\Core\Console\Commands;

use Concept\Core\Components\Path\PathManager;
use Concept\Core\Console\Commands\DbRollbackCommand;
use Illuminate\Database\Migrations\Migrator;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class DbRollbackCommandTest extends TestCase
{
    public function testExecuteRollsBackMigrationsAndPrintsSuccess(): void
    {
        $migrator = $this->createMock(Migrator::class);
        $migrator->expects(self::once())
            ->method('rollback')
            ->with('/tmp/app/database/migrations')
            ->willReturn(['2026_01_01_000000_create_users_table']);

        $pathManager = new PathManager('/tmp/app', [
            PathManager::MIGRATIONS_DIR => 'database/migrations',
        ]);

        $tester = new CommandTester(new DbRollbackCommand($migrator, $pathManager));

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

        $pathManager = new PathManager('/tmp/app', [
            PathManager::MIGRATIONS_DIR => 'database/migrations',
        ]);

        $tester = new CommandTester(new DbRollbackCommand($migrator, $pathManager));

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Nothing to rollback.', $tester->getDisplay());
    }

    public function testExecuteReturnsFailureOnException(): void
    {
        $migrator = $this->createStub(Migrator::class);
        $migrator->method('rollback')->willThrowException(new \RuntimeException('rollback fail'));

        $pathManager = new PathManager('/tmp/app', [
            PathManager::MIGRATIONS_DIR => 'database/migrations',
        ]);

        $tester = new CommandTester(new DbRollbackCommand($migrator, $pathManager));

        $exitCode = $tester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('Rollback failed: rollback fail', $tester->getDisplay());
    }
}
