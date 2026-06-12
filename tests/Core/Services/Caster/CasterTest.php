<?php declare(strict_types=1);

namespace Tests\Core\Services\Caster;

use Concept\Core\Services\Caster\Caster;
use Concept\Core\Services\Caster\Exceptions\CastingException;
use Concept\Core\Services\Config\Contracts\ConfigInterface;
use Concept\Core\Foundation\PathManager;
use Concept\Core\Foundation\PathName;
use PHPUnit\Framework\TestCase;

final class CasterTest extends TestCase
{
    private string $tmpRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->tmpRoot = sys_get_temp_dir() . '/caster-test-' . bin2hex(random_bytes(6));
        mkdir($this->tmpRoot . '/storage/cache', 0777, true);
    }

    protected function tearDown(): void
    {
        $this->removeTree($this->tmpRoot);
        parent::tearDown();
    }

    public function testCastMapsArrayToDtoClass(): void
    {
        $pathManager = new PathManager($this->tmpRoot, [
            PathName::CACHE => 'storage/cache',
        ]);

        $config = $this->createStub(ConfigInterface::class);
        $config->method('getBool')->willReturn(false);

        $caster = new Caster($pathManager, $config);

        $dto = $caster->cast(['id' => 15, 'title' => 'Post'], CasterTestDto::class);

        self::assertInstanceOf(CasterTestDto::class, $dto);
        self::assertSame(15, $dto->id);
        self::assertSame('Post', $dto->title);
    }

    public function testCastThrowsDomainExceptionOnMappingError(): void
    {
        $pathManager = new PathManager($this->tmpRoot, [
            PathName::CACHE => 'storage/cache',
        ]);

        $config = $this->createStub(ConfigInterface::class);
        $config->method('getBool')->willReturn(false);

        $caster = new Caster($pathManager, $config);

        $this->expectException(CastingException::class);
        // Using a regex to match the start of the message as Valinor appends specific details
        $this->expectExceptionMessageMatches('/^Failed to cast provided data to type: .*\. Reason: /');

        $caster->cast([], CasterRequiredDto::class);
    }

    public function testConstructorUsesFileWatchingCacheWhenDebugEnabled(): void
    {
        $pathManager = new PathManager($this->tmpRoot, [
            PathName::CACHE => 'storage/cache',
        ]);

        $config = $this->createStub(ConfigInterface::class);
        $config->method('getBool')->willReturn(true);

        $caster = new Caster($pathManager, $config);

        $dto = $caster->cast(['id' => '7', 'title' => 'Debug'], CasterTestDto::class);

        self::assertInstanceOf(CasterTestDto::class, $dto);
        self::assertSame(7, $dto->id);
        self::assertSame('Debug', $dto->title);
    }

    private function removeTree(string $path): void
    {
        if (!is_dir($path)) {
            return;
        }

        foreach (scandir($path) ?: [] as $item) {
            if ($item === '.' || $item === '..') {
                continue;
            }

            $full = $path . '/' . $item;
            if (is_dir($full)) {
                $this->removeTree($full);
            } else {
                unlink($full);
            }
        }

        rmdir($path);
    }
}

final class CasterTestDto
{
    public int $id;
    public string $title;
}

final class CasterRequiredDto
{
    public function __construct(public int $id)
    {
    }
}
