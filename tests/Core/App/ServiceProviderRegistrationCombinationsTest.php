<?php declare(strict_types=1);

namespace Tests\Core\App;

use Concept\Core\App;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Core\CrossResolvingBootableProvider;
use Tests\Fixtures\Core\FirstBootableProvider;
use Tests\Fixtures\Core\ProviderRegistrationTracker;
use Tests\Fixtures\Core\SecondBootableProvider;
use Whoops\Run as Whoops;

final class ServiceProviderRegistrationCombinationsTest extends TestCase
{
    private string $tempRoot;

    private ?App $app = null;

    protected function setUp(): void
    {
        parent::setUp();
        ProviderRegistrationTracker::reset();

        $this->tempRoot = sys_get_temp_dir() . '/concept-provider-combo-' . bin2hex(random_bytes(6));
        mkdir($this->tempRoot, 0777, true);
    }

    protected function tearDown(): void
    {
        if ($this->app !== null) {
            try {
                /** @var Whoops $whoops */
                $whoops = $this->app->getContainer()->get(Whoops::class);
                $whoops->unregister();
            } catch (\Throwable) {
            }
        }

        foreach (glob($this->tempRoot . '/*') ?: [] as $file) {
            if (is_file($file)) {
                unlink($file);
            }
        }
        if (is_dir($this->tempRoot)) {
            rmdir($this->tempRoot);
        }

        ProviderRegistrationTracker::reset();
        parent::tearDown();
    }

    public function testRegistersBootsProvidersFromSingleFileWithoutLoop(): void
    {
        $providersFile = $this->tempRoot . '/providers-single.php';
        file_put_contents(
            $providersFile,
            "<?php return ["
            . "\\Tests\\Fixtures\\Core\\FirstBootableProvider::class,"
            . "\\Tests\\Fixtures\\Core\\SecondBootableProvider::class,"
            . "\\Tests\\Fixtures\\Core\\CrossResolvingBootableProvider::class"
            . "];"
        );

        $this->app = App::create($this->tempRoot, []);
        $this->app->registerServiceProviders([$providersFile]);
        $container = $this->app->getContainer();

        self::assertSame('first', $container->get('provider.first'));
        self::assertSame('second', $container->get('provider.second'));
        self::assertSame('cross', $container->get('provider.cross'));

        self::assertCount(6, ProviderRegistrationTracker::$events);
        self::assertLessThanOrEqual(20, ProviderRegistrationTracker::$loopGuard);
        self::assertSame(1, $this->countEvent('first.register'));
        self::assertSame(1, $this->countEvent('first.boot'));
        self::assertSame(1, $this->countEvent('second.register'));
        self::assertSame(1, $this->countEvent('second.boot'));
        self::assertSame(1, $this->countEvent('cross.register'));
        self::assertSame(1, $this->countEvent('cross.boot'));
    }

    public function testRegistersProvidersAcrossMultipleFilesWithoutLoop(): void
    {
        $providersFileA = $this->tempRoot . '/providers-a.php';
        $providersFileB = $this->tempRoot . '/providers-b.php';

        file_put_contents(
            $providersFileA,
            "<?php return ["
            . "\\Tests\\Fixtures\\Core\\FirstBootableProvider::class,"
            . "\\Tests\\Fixtures\\Core\\SecondBootableProvider::class"
            . "];"
        );
        file_put_contents(
            $providersFileB,
            "<?php return [\\Tests\\Fixtures\\Core\\CrossResolvingBootableProvider::class];"
        );

        $this->app = App::create($this->tempRoot, []);
        $this->app->registerServiceProviders([$providersFileA, $providersFileB]);
        $container = $this->app->getContainer();

        self::assertSame('first', $container->get('provider.first'));
        self::assertSame('second', $container->get('provider.second'));
        self::assertSame('cross', $container->get('provider.cross'));

        self::assertCount(6, ProviderRegistrationTracker::$events);
        self::assertLessThanOrEqual(20, ProviderRegistrationTracker::$loopGuard);
        self::assertSame(1, $this->countEvent('first.register'));
        self::assertSame(1, $this->countEvent('first.boot'));
        self::assertSame(1, $this->countEvent('second.register'));
        self::assertSame(1, $this->countEvent('second.boot'));
        self::assertSame(1, $this->countEvent('cross.register'));
        self::assertSame(1, $this->countEvent('cross.boot'));
    }

    private function countEvent(string $event): int
    {
        return count(array_filter(
            ProviderRegistrationTracker::$events,
            static fn (string $item): bool => $item === $event
        ));
    }
}

