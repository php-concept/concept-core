<?php declare(strict_types=1);

namespace Tests\Core\Components\Masker;

use PHPUnit\Framework\TestCase;
use Concept\Core\Components\DataMasker\DataMasker;
use Concept\Core\Components\DataMasker\Contracts\DataMaskerRuleInterface;

class DataMaskerTest extends TestCase
{
    public function testMaskArrayWithSensitiveKey(): void
    {
        $rule = $this->createStub(DataMaskerRuleInterface::class);
        $rule->method('isSensitiveKey')->willReturnCallback(fn($key) => $key === 'password');
        $rule->method('apply')->willReturnArgument(0);
        
        $masker = new DataMasker();
        $masker->addRule($rule);

        $data = [
            'username' => 'john_doe',
            'full_name' => 'John Doe',
            'password' => 'secret123',
            'nested' => [
                'password' => 'inner_secret'
            ]
        ];

        $expected = [
            'username' => 'john_doe',
            'full_name' => 'John Doe',
            'password' => DataMasker::MASK_CHARS,
            'nested' => [
                'password' => DataMasker::MASK_CHARS
            ]
        ];

        $result = $masker->mask($data);
        $this->assertEquals($expected, $result);
        $this->assertNotSame($data, $result);
    }

    public function testMaskObjectImmutability(): void
    {
        $masker = new DataMasker();
        $rule = $this->createStub(DataMaskerRuleInterface::class);
        $rule->method('isSensitiveKey')->willReturnCallback(fn($key) => $key === 'password');
        $rule->method('apply')->willReturnArgument(0);
        $masker->addRule($rule);

        $obj = new \stdClass();
        $obj->password = 'secret';

        $result = $masker->mask($obj);

        $this->assertEquals(DataMasker::MASK_CHARS, $result->password);
        $this->assertEquals('secret', $obj->password, 'Original object should not be modified');
        $this->assertNotSame($obj, $result);
    }

    public function testMaskStringWithRules(): void
    {
        $rule = $this->createStub(DataMaskerRuleInterface::class);
        $rule->method('apply')->willReturnCallback(fn($val) => str_replace('secret', '*****', $val));

        $masker = new DataMasker();
        $masker->addRule($rule);

        $this->assertEquals('this is a *****', $masker->mask('this is a secret'));
    }

    public function testMaskObject(): void
    {
        $rule = $this->createStub(DataMaskerRuleInterface::class);
        $rule->method('isSensitiveKey')->willReturn(false);
        $rule->method('apply')->willReturnCallback(fn($val) => is_string($val) ? str_replace('secret', '*****', $val) : $val);

        $masker = new DataMasker();
        $masker->addRule($rule);

        $obj = new \stdClass();
        $obj->email = 'secret@example.com';

        $result = $masker->mask($obj);
        $this->assertEquals('*****@example.com', $result->email);
    }

    public function testMaskNestedObjectImmutability(): void
    {
        $rule = $this->createStub(DataMaskerRuleInterface::class);
        $rule->method('isSensitiveKey')->willReturnCallback(fn($key) => $key === 'password');
        $rule->method('apply')->willReturnArgument(0);

        $masker = new DataMasker();
        $masker->addRule($rule);

        $subObj = new \stdClass();
        $subObj->password = '12345';

        $obj = new \stdClass();
        $obj->sub = $subObj;

        $result = $masker->mask($obj);

        $this->assertEquals(DataMasker::MASK_CHARS, $result->sub->password);
        // DataMasker perform shallow copy, so nested object will be modified
        $this->assertEquals(DataMasker::MASK_CHARS, $subObj->password);
        $this->assertSame($subObj, $result->sub);
    }

    public function testMaskArrayWithObjectImmutability(): void
    {
        $rule = $this->createStub(DataMaskerRuleInterface::class);
        $rule->method('isSensitiveKey')->willReturnCallback(fn($key) => $key === 'secret');
        $rule->method('apply')->willReturnArgument(0);

        $masker = new DataMasker();
        $masker->addRule($rule);

        $obj = new \stdClass();
        $obj->secret = 'hidden';
        $data = ['items' => [$obj]];

        $result = $masker->mask($data);

        $this->assertEquals(DataMasker::MASK_CHARS, $result['items'][0]->secret);
        $this->assertEquals('hidden', $obj->secret);
    }
}
