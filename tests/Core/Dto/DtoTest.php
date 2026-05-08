<?php declare(strict_types=1);

namespace Tests\Core;

use Concept\Core\Dto\Dto;
use PHPUnit\Framework\TestCase;

final class DtoTest extends TestCase
{
    public function testEmptyDtoToArrayIsEmpty(): void
    {
        $dto = new Dto();

        self::assertSame([], $dto->toArray());
    }

    public function testPublicPropertiesAppearInToArray(): void
    {
        $dto = new class extends Dto {
            public function __construct(public string $label = 'x', public int $count = 3)
            {
            }
        };

        self::assertSame(['label' => 'x', 'count' => 3], $dto->toArray());
    }
}
