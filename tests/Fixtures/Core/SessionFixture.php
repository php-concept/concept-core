<?php declare(strict_types=1);

namespace Tests\Fixtures\Core;

use Concept\Core\Services\Session\Contracts\SessionInterface;
use Concept\Core\Services\Session\FlashBag;
use Concept\Core\Services\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class SessionFixture
{
    public static function make(): SessionInterface
    {
        return new Session(new MockArraySessionStorage(), flashes: new FlashBag());
    }
}
