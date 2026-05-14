<?php declare(strict_types=1);

namespace Tests\Core\Console\Commands;

use Concept\Core\Components\Config\Contracts\ConfigInterface;
use Concept\Core\Console\Commands\DbSeedersListCommand;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;

final class DbSeedersListCommandTest extends TestCase
{
    public function testShowsWarningWhenNoSeedersConfigured(): void
    {
        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::once())
            ->method('get')
            ->with('seeders.list', [])
            ->willReturn([]);

        $tester = new CommandTester(new DbSeedersListCommand($config));

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

        $config = $this->createMock(ConfigInterface::class);
        $config->expects(self::once())
            ->method('get')
            ->with('seeders.list', [])
            ->willReturn($seeders);

        $tester = new CommandTester(new DbSeedersListCommand($config));

        $exitCode = $tester->execute([]);
        $display = $tester->getDisplay();

        self::assertSame(Command::SUCCESS, $exitCode);
        self::assertStringContainsString('Seeders List', $display);
        self::assertStringContainsString('App\\Database\\Seeders\\DatabaseSeeder', $display);
        self::assertStringContainsString('App\\Database\\Seeders\\UserSeeder', $display);
        self::assertStringContainsString('End of seeders list.', $display);
    }
}
