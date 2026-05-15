<?php declare(strict_types=1);

namespace Tests\Core\Console\Commands;

use Concept\Core\Components\Database\Registries\SeederRegistry;
use Concept\Core\Console\Commands\DbSeedersListCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class DbSeedersListCommandTest extends TestCase
{
    public function testShowsWarningWhenNoSeedersConfigured(): void
    {
        $seederRegistry = $this->createMock(SeederRegistry::class);
        $seederRegistry->expects(self::once())
            ->method('all')
            ->willReturn([]);

        $tester = new CommandTester(new DbSeedersListCommand($seederRegistry));

        $exitCode = $tester->execute([]);

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('No seeders found.', $tester->getDisplay());
    }

    public function testDisplaysConfiguredSeeders(): void
    {
        $seeders = [
            'App\\Database\\Seeders\\DatabaseSeeder',
            'App\\Database\\Seeders\\UserSeeder',
        ];

        $seederRegistry = $this->createMock(SeederRegistry::class);
        $seederRegistry->expects(self::once())
            ->method('all')
            ->willReturn($seeders);

        $tester = new CommandTester(new DbSeedersListCommand($seederRegistry));

        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Seeders List', $display);
        self::assertStringContainsString('App\\Database\\Seeders\\DatabaseSeeder', $display);
        self::assertStringContainsString('App\\Database\\Seeders\\UserSeeder', $display);
        self::assertStringContainsString('End of seeders list.', $display);
    }
}
