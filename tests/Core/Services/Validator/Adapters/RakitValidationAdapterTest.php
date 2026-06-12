<?php declare(strict_types=1);

namespace Tests\Core\Services\Validator\Adapters;

use Concept\Core\Services\Validator\Adapters\RakitValidationAdapter;
use PHPUnit\Framework\TestCase;
use Rakit\Validation\ErrorBag;
use Rakit\Validation\Validation as RakitValidation;

final class RakitValidationAdapterTest extends TestCase
{
    public function testValidateDelegatesToUnderlyingValidation(): void
    {
        $validation = $this->getMockBuilder(RakitValidation::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['validate'])
            ->getMock();
        $validation->expects(self::once())->method('validate');

        $adapter = new RakitValidationAdapter($validation);

        $adapter->validate();
    }

    public function testIsValidUsesFailsInverse(): void
    {
        $validation = $this->createStub(RakitValidation::class);
        $validation->method('fails')->willReturnOnConsecutiveCalls(false, true);

        $adapter = new RakitValidationAdapter($validation);

        self::assertTrue($adapter->isValid());
        self::assertFalse($adapter->isValid());
    }

    public function testGetValidDataAndErrorsAndAliasesDelegation(): void
    {
        $errorBag = $this->createStub(ErrorBag::class);
        $errorBag->method('toArray')->willReturn(['email' => ['required']]);

        $validation = $this->getMockBuilder(RakitValidation::class)
            ->disableOriginalConstructor()
            ->onlyMethods(['getValidData', 'errors', 'setAliases', 'setMessages', 'setTranslations'])
            ->getMock();

        $validation->method('getValidData')->willReturn(['email' => 'user@test.dev']);
        $validation->method('errors')->willReturn($errorBag);
        $validation->expects(self::once())->method('setAliases')->with(['email' => 'Email']);
        $validation->expects(self::once())->method('setMessages')->with(['required' => 'Required']);
        $validation->expects(self::once())->method('setTranslations')->with(['or' => 'or']);

        $adapter = new RakitValidationAdapter($validation);

        self::assertSame(['email' => 'user@test.dev'], $adapter->getValidData());
        self::assertSame(['email' => ['required']], $adapter->getErrors());
        $adapter->setAliases(['email' => 'Email']);
        $adapter->setMessages(['required' => 'Required']);
        $adapter->setTranslations(['or' => 'or']);
    }
}
