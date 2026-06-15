<?php declare(strict_types=1);

namespace Tests\Core\Services\Session;

use Concept\Core\Services\Session\FlashBag;
use Concept\Core\Services\Session\FlashType;
use Concept\Core\Services\Session\Session;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class FlashBagTest extends TestCase
{
    public function testTypedAddMethodsAppendMessages(): void
    {
        $flash = new FlashBag();

        $flash->addError('First error');
        $flash->addError('Second error');
        $flash->addInfo('Info');
        $flash->addSuccess('Success');
        $flash->addWarning('Warning');

        self::assertSame(['First error', 'Second error'], $flash->peek(FlashType::ERROR));
        self::assertSame(['Info'], $flash->peek(FlashType::INFO));
        self::assertSame(['Success'], $flash->peek(FlashType::SUCCESS));
        self::assertSame(['Warning'], $flash->peek(FlashType::WARNING));
    }

    public function testInheritsSymfonyFlashBagBehavior(): void
    {
        $flash = new FlashBag();
        $flash->set('custom', 'value');

        self::assertTrue($flash->has('custom'));
        self::assertSame(['custom'], $flash->keys());
        self::assertSame(['value'], $flash->get('custom'));
        self::assertSame(['custom' => ['value']], $flash->all());
    }
}

final class SessionTest extends TestCase
{
    public function testInheritsSymfonySessionBehaviorAndReturnsFlashBag(): void
    {
        $flashBag = new FlashBag();
        $session = new Session(new MockArraySessionStorage(), flashes: $flashBag);

        $session->set('name', 'Concept');
        $session->start();

        self::assertTrue($session->isStarted());
        self::assertTrue($session->has('name'));
        self::assertSame('Concept', $session->get('name'));
        self::assertSame('Concept', $session->remove('name'));
        self::assertFalse($session->has('name'));
        self::assertSame($flashBag, $session->getFlashBag());
    }

    public function testExposesFullSymfonySessionApi(): void
    {
        $session = new Session(new MockArraySessionStorage(), flashes: new FlashBag());

        self::assertTrue(method_exists($session, 'invalidate'));
        self::assertTrue(method_exists($session, 'migrate'));
        self::assertTrue(method_exists($session, 'clear'));
    }
}
