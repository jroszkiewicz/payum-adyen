<?php

declare(strict_types=1);

namespace Tests\Payum\Adyen;

use Payum\Adyen\AdyenGatewayFactory;
use Payum\Core\GatewayFactory;
use PHPUnit\Framework\TestCase;
use ReflectionClass;

class AdyenGatewayFactoryTest extends TestCase
{
    public function testShouldSubClassGatewayFactory(): void
    {
        $rc = new ReflectionClass(AdyenGatewayFactory::class);
        
        self::assertTrue($rc->isSubclassOf(GatewayFactory::class));
    }
    
    public function testSouldBeConstructedWithoutAnyArguments(): void
    {
        new AdyenGatewayFactory();
    }
    
    public function testShouldConfigContainDefaultOptions(): void
    {
        $factory = new AdyenGatewayFactory();
        
        $config = $factory->createConfig();
        
        $this->assertInternalType('array', $config);
        
        self::assertArrayHasKey('payum.default_options', $config);
        
        $options = [
            'skinCode'               => '',
            'merchantAccount'        => '',
            'hmacKey'                => '',
            'sandbox'                => true,
            'notification_method'    => 'basic',
            'default_payment_fields' => [],
        ];
        
        self::assertEquals($options, $config['payum.default_options']);
    }
    
    public function testShouldConfigContainFactoryNameAndTitle(): void
    {
        $factory = new AdyenGatewayFactory();
        
        $config = $factory->createConfig();
        
        $this->assertInternalType('array', $config);
        
        self::assertArrayHasKey('payum.factory_name', $config);
        self::assertEquals('adyen', $config['payum.factory_name']);
        
        self::assertArrayHasKey('payum.factory_title', $config);
        self::assertEquals('Adyen', $config['payum.factory_title']);
    }
}
