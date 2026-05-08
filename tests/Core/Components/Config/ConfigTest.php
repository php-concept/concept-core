<?php declare(strict_types=1);

namespace Tests\Core;

use Concept\Core\Components\Config\Config;
use Noodlehaus\ConfigInterface as NoodlehausConfigInterface;
use PHPUnit\Framework\TestCase;

final class ConfigTest extends TestCase
{
    public function testDelegatesGetSetHasAndAllToUnderlyingConfig(): void
    {
        $base = new InMemoryNoodlehausConfig([
            'app.name' => 'concept',
        ]);

        $config = new Config($base);

        self::assertSame('concept', $config->get('app.name'));
        self::assertSame('fallback', $config->get('missing', 'fallback'));
        self::assertTrue($config->has('app.name'));
        self::assertFalse($config->has('missing'));

        $config->set('app.debug', true);

        self::assertTrue($config->has('app.debug'));
        self::assertSame([
            'app.name' => 'concept',
            'app.debug' => true,
        ], $config->all());
    }

    public function testGetStringCastsScalarAndFallsBackForArray(): void
    {
        $base = new InMemoryNoodlehausConfig([
            'app.name' => 'concept',
            'app.meta' => ['a' => 1],
            'app.version' => 2,
        ]);

        $config = new Config($base);

        self::assertSame('concept', $config->getString('app.name'));
        self::assertSame('2', $config->getString('app.version'));
        self::assertSame('fallback', $config->getString('app.meta', 'fallback'));
    }

    public function testGetIntReturnsDefaultForNonNumericValues(): void
    {
        $base = new InMemoryNoodlehausConfig([
            'cache.ttl' => '30',
            'cache.strategy' => 'forever',
        ]);

        $config = new Config($base);

        self::assertSame(30, $config->getInt('cache.ttl'));
        self::assertSame(10, $config->getInt('cache.strategy', 10));
    }

    public function testGetBoolReadsBooleanLikeValues(): void
    {
        $base = new InMemoryNoodlehausConfig([
            'app.debug' => 'true',
            'app.enabled' => 'false',
            'app.maintenance' => null,
        ]);

        $config = new Config($base);

        self::assertTrue($config->getBool('app.debug'));
        self::assertFalse($config->getBool('app.enabled', true));
        self::assertTrue($config->getBool('app.maintenance', true));
    }
}

final class InMemoryNoodlehausConfig implements NoodlehausConfigInterface
{
    /** @var array<string, mixed> */
    private array $items;

    /** @param array<string, mixed> $items */
    public function __construct(array $items)
    {
        $this->items = $items;
    }

    public function get($key, $default = null)
    {
        return array_key_exists((string) $key, $this->items) ? $this->items[$key] : $default;
    }

    public function set($key, $value)
    {
        $this->items[$key] = $value;
    }

    public function has($key)
    {
        return array_key_exists((string) $key, $this->items);
    }

    public function all()
    {
        return $this->items;
    }
}
