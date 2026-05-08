<?php declare(strict_types=1);

namespace Tests\Core;

use Concept\Core\Components\Csrf\CsrfTokenManager;
use Concept\Core\Http\SessionKey;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class CsrfTokenManagerTest extends TestCase
{
    public function testGetTokenGeneratesAndReusesToken(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $manager = new CsrfTokenManager($session);

        $first = $manager->getToken();
        $second = $manager->getToken();

        self::assertNotSame('', $first);
        self::assertSame($first, $second);
        self::assertTrue($session->has(SessionKey::CSRF_TOKEN));
    }

    public function testGetTokenReturnsEmptyStringWhenSessionValueIsNotString(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set(SessionKey::CSRF_TOKEN, ['unexpected']);

        $manager = new CsrfTokenManager($session);

        self::assertSame('', $manager->getToken());
    }

    public function testValidateReturnsFalseWhenTokenMissingOrInvalid(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $manager = new CsrfTokenManager($session);

        self::assertFalse($manager->validate(null));
        self::assertFalse($manager->validate(''));

        $manager->getToken();
        self::assertFalse($manager->validate('wrong-token'));
    }

    public function testValidateReturnsTrueWhenTokenMatches(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $manager = new CsrfTokenManager($session);

        $token = $manager->getToken();

        self::assertTrue($manager->validate($token));
    }

    public function testValidateReturnsFalseWhenSessionTokenIsNotString(): void
    {
        $session = new Session(new MockArraySessionStorage());
        $session->set(SessionKey::CSRF_TOKEN, ['unexpected']);

        $manager = new CsrfTokenManager($session);

        self::assertFalse($manager->validate('token'));
    }
}
