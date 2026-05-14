<?php declare(strict_types=1);

namespace Tests\Core\Console\Commands;

use Concept\Core\Components\Database\SeederManager;
use Concept\Core\Console\Commands\DbSeedCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class DbSeedCommandTest extends TestCase
{
    public function testExecuteRunsAllSeedersAndPrintsSuccess(): void
    {
        $seeder = $this->createMock(SeederManager::class);
        $seeder->expects(self::once())
            ->method('run')
            ->willReturn(['DatabaseSeeder', 'UserSeeder']);

        $tester = new CommandTester(new DbSeedCommand($seeder));

        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Starting database seeding', $display);
        self::assertStringContainsString('Seeded: DatabaseSeeder', $display);
        self::assertStringContainsString('Seeded: UserSeeder', $display);
        self::assertStringContainsString('Database seeding completed successfully.', $display);
    }

    public function testExecuteRunsSpecificSeederClass(): void
    {
        $class = 'App\\Database\\Seeders\\UserSeeder';

        $seeder = $this->createMock(SeederManager::class);
        $seeder->expects(self::never())->method('run');
        $seeder->expects(self::once())
            ->method('resolveAndRun')
            ->with($class);

        $tester = new CommandTester(new DbSeedCommand($seeder));

        $exitCode = $tester->execute(['--class' => $class]);
        $display = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Starting database seeding', $display);
        self::assertStringContainsString('Seeded: ' . $class, $display);
        self::assertStringContainsString('Database seeding completed successfully.', $display);
    }

    public function testExecuteReturnsFailureWhenSeederThrows(): void
    {
        $seeder = $this->createStub(SeederManager::class);
        $seeder->method('run')->willThrowException(new \RuntimeException('seeding failed'));

        $tester = new CommandTester(new DbSeedCommand($seeder));

        $exitCode = $tester->execute([]);

        self::assertSame(Command::FAILURE, $exitCode);
        self::assertStringContainsString('seeding failed', $tester->getDisplay());
    }
}
