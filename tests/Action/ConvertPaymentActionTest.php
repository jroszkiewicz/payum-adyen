<?php

declare(strict_types=1);

namespace Tests\Payum\Adyen\Action;

use Iterator;
use Payum\Adyen\Action\ConvertPaymentAction;
use Payum\Core\Model\Payment;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;
use Payum\Core\Tests\GenericActionTest;

class ConvertPaymentActionTest extends GenericActionTest
{
    protected $actionClass = ConvertPaymentAction::class;
    
    protected $requestClass = Convert::class;
    
    public function provideSupportedRequests(): Iterator
    {
        return [
            [new $this->requestClass(new Payment(), 'array')],
            [new $this->requestClass($this->getMock(PaymentInterface::class), 'array')],
            [new $this->requestClass(new Payment(), 'array', $this->getMock('Payum\Core\Security\TokenInterface'))],
        ];
    }
    
    public function provideNotSupportedRequests(): \Iterator
    {
        return [
            ['foo'],
            [['foo']],
            [new \stdClass()],
            [$this->getMockForAbstractClass('Payum\Core\Request\Generic', [[]])],
            [new $this->requestClass(new \stdClass(), 'array')],
            [new $this->requestClass(new Payment(), 'foobar')],
            [new $this->requestClass($this->getMock(PaymentInterface::class), 'foobar')],
        ];
    }
    
    public function testShouldCorrectlyConvertOrderToDetailsAndSetItBack(): void
    {
        $payment = new Payment();
        $payment->setNumber('theNumber');
        $payment->setCurrencyCode('EUR');
        $payment->setTotalAmount(1000);
        $payment->setClientId('theClientId');
        $payment->setClientEmail('theClientEmail');
        
        $action = new ConvertPaymentAction();
        
        $action->execute($convert = new Convert($payment, 'array'));
        
        $details = $convert->getResult();
        
        self::assertNotEmpty($details);
        
        self::assertArrayHasKey('merchantReference', $details);
        self::assertEquals('theNumber', $details['merchantReference']);
        
        self::assertArrayHasKey('paymentAmount', $details);
        self::assertEquals(1000, $details['paymentAmount']);
        
        self::assertArrayHasKey('shopperEmail', $details);
        self::assertEquals('theClientEmail', $details['shopperEmail']);
        
        self::assertArrayHasKey('currencyCode', $details);
        self::assertEquals('EUR', $details['currencyCode']);
        
        self::assertArrayHasKey('shopperReference', $details);
        self::assertEquals('theClientId', $details['shopperReference']);
    }
}
