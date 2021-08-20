<?php

declare(strict_types=1);

namespace Tests\Payum\Adyen;

use Payum\Adyen\Api;
use Payum\Core\Exception\LogicException;
use Payum\Core\HttpClientInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class ApiTest extends TestCase
{
    public function testCouldBeConstructedWithOptionsOnly(): void
    {
        $api = new Api([
            'skinCode'        => 'skin',
            'merchantAccount' => 'account',
            'hmacKey'         => '4468',
            'sandbox'         => true,
        ]);
        
        self::assertAttributeInstanceOf(HttpClientInterface::class, 'client', $api);
    }
    
    public function testCouldBeConstructedWithOptionsAndHttpClient(): void
    {
        $client = $this->createHttpClientMock();
        
        $api = new Api([
            'skinCode'        => 'skin',
            'merchantAccount' => 'account',
            'hmacKey'         => '4468',
            'sandbox'         => true,
        ], $client);
        
        self::assertAttributeSame($client, 'client', $api);
    }
    
    public function testThrowIfRequiredOptionsNotSetInConstructor(): void
    {
        $this->expectExceptionMessage("The skinCode, merchantAccount, hmacKey fields are required.");
        $this->expectException(LogicException::class);
        
        new Api([]);
    }
    
    public function testThrowIfSandboxOptionsNotBooleanInConstructor(): void
    {
        $this->expectExceptionMessage("The boolean sandbox option must be set.");
        $this->expectException(LogicException::class);
        
        new Api([
            'skinCode'        => 'skin',
            'merchantAccount' => 'account',
            'hmacKey'         => '4468',
            'sandbox'         => 'notABool',
        ]);
    }
    
    public function testShouldReturnPostArrayWithMerchantAccountOnPrepareFields(): void
    {
        $api = new Api([
            'skinCode'        => 'skin',
            'merchantAccount' => 'account',
            'hmacKey'         => '4468',
            'sandbox'         => true,
        ], $this->createHttpClientMock());
        
        $post = $api->prepareFields([
            'merchantAccount' => 'account',
        ]);
        
        $this->assertInternalType('array', $post);
        self::assertArrayHasKey('merchantAccount', $post);
    }
    
    public function testShouldFilterNotSupportedOnPrepareFields(): void
    {
        $api = new Api([
            'skinCode'        => 'skin',
            'merchantAccount' => 'account',
            'hmacKey'         => '4468',
            'sandbox'         => true,
        ], $this->createHttpClientMock());
        
        $post = $api->prepareFields([
            'FOO' => 'fooVal',
            'BAR' => 'barVal',
        ]);
        
        $this->assertInternalType('array', $post);
        self::assertArrayNotHasKey('FOO', $post);
        self::assertArrayNotHasKey('BAR', $post);
    }
    
    public function testShouldReturnFalseIfVerifySignNotSetToParams(): void
    {
        $api = new Api([
            'skinCode'        => 'skin',
            'merchantAccount' => 'account',
            'hmacKey'         => '4468',
            'sandbox'         => true,
        ], $this->createHttpClientMock());
        
        self::assertFalse($api->verifySign([]));
    }
    
    public function testShouldReturnFalseIfHmacKeyMisMatched(): void
    {
        $params      = [
            'foo' => 'fooVal',
            'bar' => 'barVal',
        ];
        $invalidSign = 'invalidHash';
        
        $api = new Api([
            'skinCode'        => 'skin',
            'merchantAccount' => 'account',
            'hmacKey'         => '4468',
            'sandbox'         => true,
        ], $this->createHttpClientMock());
        
        // Guard
        self::assertNotEquals($invalidSign, $api->merchantSig($params));
        
        $params['merchantSig'] = $invalidSign;
        
        self::assertFalse($api->verifySign($params));
    }
    
    public function testShouldReturnTrueIfHmacKeyMatched(): void
    {
        $params = [
            'foo' => 'fooVal',
            'bar' => 'barVal',
        ];
        
        $api = new Api([
            'skinCode'        => 'skin',
            'merchantAccount' => 'account',
            'hmacKey'         => '4468',
            'sandbox'         => true,
        ], $this->createHttpClientMock());
        
        $params['merchantSig'] = $api->merchantSig($params);
        
        self::assertTrue($api->verifySign($params));
    }
    
    /**
     * @return MockObject|HttpClientInterface
     */
    protected function createHttpClientMock()
    {
        return $this->createMock(HttpClientInterface::class);
    }
}
