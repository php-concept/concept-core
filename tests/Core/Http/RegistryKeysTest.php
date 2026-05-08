<?php declare(strict_types=1);

namespace Tests\Core\Http;

use Concept\Core\Http\Protocol\HttpHeader;
use Concept\Core\Http\Protocol\HttpMethod;
use Concept\Core\Http\Protocol\HttpValue;
use Concept\Core\Http\Protocol\UrlComponent;
use Concept\Core\Http\RequestAttribute;
use Concept\Core\Http\SessionKey;
use Concept\Core\Http\ViewKey;
use PHPUnit\Framework\TestCase;

final class RegistryKeysTest extends TestCase
{
    public function testHttpMethodConstantsAreUppercaseAndStable(): void
    {
        self::assertSame('GET', HttpMethod::GET);
        self::assertSame('POST', HttpMethod::POST);
        self::assertSame('DELETE', HttpMethod::DELETE);
    }

    public function testHttpHeaderAndValueConstantsMatchExpectedContracts(): void
    {
        self::assertSame('Content-Type', HttpHeader::CONTENT_TYPE);
        self::assertSame('X-Requested-With', HttpHeader::X_REQUESTED_WITH);
        self::assertSame('application/json', HttpValue::JSON);
        self::assertSame('XMLHttpRequest', HttpValue::XML_HTTP_REQUEST);
    }

    public function testRequestSessionViewAndUrlKeysRemainStable(): void
    {
        self::assertSame('safe_back_url', RequestAttribute::SAFE_BACK_URL);
        self::assertSame('_csrf_token', SessionKey::CSRF_TOKEN);
        self::assertSame('csrf_token', ViewKey::CSRF_TOKEN);
        self::assertSame('host', UrlComponent::HOST);
        self::assertSame('path', UrlComponent::PATH);
    }
}
