<?php declare(strict_types=1);

namespace Tests\Core\Http\Security;

use Concept\Core\Http\Security\CsrfTokenManager;
use Concept\Core\Services\Session\SessionKey;
use PHPUnit\Framework\TestCase;
use Tests\Fixtures\Core\SessionFixture;

final class CsrfTokenManagerTest extends TestCase
{
    public function testGetTokenGeneratesAndReusesToken(): void
    {
        $session = SessionFixture::make();
        $manager = new CsrfTokenManager($session);

        $first = $manager->getToken();
        $second = $manager->getToken();

        self::assertNotSame('', $first);
        self::assertSame($first, $second);
        self::assertTrue($session->has(SessionKey::CSRF_TOKEN));
    }

    public function testGetTokenReturnsEmptyStringWhenSessionValueIsNotString(): void
    {
        $session = SessionFixture::make();
        $session->set(SessionKey::CSRF_TOKEN, ['unexpected']);

        $manager = new CsrfTokenManager($session);

        self::assertSame('', $manager->getToken());
    }

    public function testValidateReturnsFalseWhenTokenMissingOrInvalid(): void
    {
        $session = SessionFixture::make();
        $manager = new CsrfTokenManager($session);

        self::assertFalse($manager->validate(null));
        self::assertFalse($manager->validate(''));

        $manager->getToken();
        self::assertFalse($manager->validate('wrong-token'));
    }

    public function testValidateReturnsTrueWhenTokenMatches(): void
    {
        $session = SessionFixture::make();
        $manager = new CsrfTokenManager($session);

        $token = $manager->getToken();

        self::assertTrue($manager->validate($token));
    }

    public function testValidateReturnsFalseWhenSessionTokenIsNotString(): void
    {
        $session = SessionFixture::make();
        $session->set(SessionKey::CSRF_TOKEN, ['unexpected']);

        $manager = new CsrfTokenManager($session);

        self::assertFalse($manager->validate('token'));
    }
}
