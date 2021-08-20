<?php

declare(strict_types=1);

namespace Tests\Payum\Adyen\Action;

use ArrayObject;
use Payum\Adyen\Action\NotifyAction;
use Payum\Adyen\Api;
use Payum\Core\Action\GatewayAwareAction;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayInterface;
use Payum\Core\Request\GetHttpRequest;
use Payum\Core\Request\Notify;
use Payum\Core\Tests\GenericActionTest;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use stdClass;

class NotifyActionTest extends GenericActionTest
{
    protected $actionClass = NotifyAction::class;
    
    protected $requestClass = Notify::class;
    
    public function testShouldBeSubClassOfGatewayAwareAction(): void
    {
        $rc = new ReflectionClass(NotifyAction::class);
        
        self::assertTrue($rc->isSubclassOf(GatewayAwareAction::class));
    }
    
    public function testShouldImplementApiAwareInterface(): void
    {
        $rc = new ReflectionClass(NotifyAction::class);
        
        self::assertTrue($rc->implementsInterface(ApiAwareInterface::class));
    }
    
    public function testShouldAllowSetApi(): void
    {
        $expectedApi = $this->createApiMock();
        
        $action = new NotifyAction();
        $action->setApi($expectedApi);
        
        self::assertAttributeSame($expectedApi, 'api', $action);
    }
    
    public function testThrowIfUnsupportedApiGiven(): void
    {
        $this->expectException(UnsupportedApiException::class);
        
        $action = new NotifyAction();
        
        $action->setApi(new stdClass());
    }
    
    public function testShouldSetErrorIfQueryDoesNotHaveMerchantReference(): void
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class))
            ->willReturnCallback(function (GetHttpRequest $request) {
                $request->request = [];
            });
        
        $apiMock = $this->createApiMock();
        
        $action = new NotifyAction();
        $action->setGateway($gatewayMock);
        $action->setApi($apiMock);
        
        $action->execute($notify = new Notify([]));
        $model = $notify->getModel();
        $this->assertSame(401, $model['response_status']);
    }
    
    public function testShouldSetErrorIfDetailsMerchantReferenceDoesNotExist(): void
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class))
            ->willReturnCallback(function (GetHttpRequest $request) {
                $request->request = [
                    'merchantReference' => 'SomeReference',
                ];
            });
        
        $apiMock = $this->createApiMock();
        
        $action = new NotifyAction();
        $action->setGateway($gatewayMock);
        $action->setApi($apiMock);
        
        $details = new ArrayObject([]);
        
        $action->execute($notify = new Notify($details));
        $model = $notify->getModel();
        $this->assertSame(402, $model['response_status']);
    }
    
    public function testShouldSetErrorIfMerchantReferenceDoesNotMatchExpected(): void
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class))
            ->willReturnCallback(function (GetHttpRequest $request) {
                $request->request = [
                    'merchantReference' => 'SomeReference',
                ];
            });
        
        $apiMock = $this->createApiMock();
        
        $action = new NotifyAction();
        $action->setGateway($gatewayMock);
        $action->setApi($apiMock);
        
        $details = new ArrayObject([
            'merchantReference' => 'SomeReference2',
        ]);
        
        $action->execute($notify = new Notify($details));
        $model = $notify->getModel();
        $this->assertSame(402, $model['response_status']);
    }
    
    public function testShouldSetErrorIfQuerySignDoesNotMatchExpected(): void
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class))
            ->willReturnCallback(function (GetHttpRequest $request) {
                $request->request = ['merchantReference' => 'SomeReference'];
            });
        
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('verifyNotification')
            ->willReturn(false);
        
        $action = new NotifyAction();
        $action->setGateway($gatewayMock);
        $action->setApi($apiMock);
        
        $details = new ArrayObject([
            'merchantReference' => 'SomeReference'
        ]);
        
        $action->execute($notify = new Notify($details));
        $model = $notify->getModel();
        $this->assertSame(403, $model['response_status']);
    }
    
    public function testShouldSetRefusedIfNotificationCodeIsAuthorizedAndNotSuccess(): void
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class))
            ->willReturnCallback(function (GetHttpRequest $request) {
                $request->request = [
                    'merchantReference' => 'SomeReference',
                    'eventCode'         => 'AUTHORISATION',
                    'success'           => 'false',
                    'reason'            => 'Reason',
                ];
            });
        
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('verifyNotification')
            ->willReturn(true);
        
        $action = new NotifyAction();
        $action->setGateway($gatewayMock);
        $action->setApi($apiMock);
        
        $details = new ArrayObject([
            'merchantReference' => 'SomeReference',
        ]);
        
        $action->execute($notify = new Notify($details));
        $model = $notify->getModel();
        $this->assertSame(200, $model['response_status']);
        $this->assertSame('REFUSED', $model['authResult']);
    }
    
    public function testShouldSetAuthorisedIfNotificationCodeIsAuthorizedAndSuccess(): void
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class))
            ->willReturnCallback(function (GetHttpRequest $request) {
                $request->request = [
                    'merchantReference' => 'SomeReference',
                    'eventCode'         => 'AUTHORISATION',
                    'success'           => 'true',
                    'reason'            => '',
                ];
            });
        
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('verifyNotification')
            ->willReturn(true);
        
        $action = new NotifyAction();
        $action->setGateway($gatewayMock);
        $action->setApi($apiMock);
        
        $details = new ArrayObject([
            'merchantReference' => 'SomeReference',
        ]);
        
        $action->execute($notify = new Notify($details));
        $model = $notify->getModel();
        $this->assertSame(200, $model['response_status']);
        $this->assertSame('AUTHORISED', $model['authResult']);
    }
    
    public function testShouldSetOkIfVerifyRequestIsOk(): void
    {
        $gatewayMock = $this->createGatewayMock();
        $gatewayMock
            ->expects($this->once())
            ->method('execute')
            ->with($this->isInstanceOf(GetHttpRequest::class))
            ->willReturnCallback(function (GetHttpRequest $request) {
                $request->request = [
                    'merchantReference' => 'SomeReference',
                    'authResult'        => 'result',
                ];
            });
        
        $apiMock = $this->createApiMock();
        $apiMock
            ->expects($this->once())
            ->method('verifyNotification')
            ->willReturn(true);
        
        $action = new NotifyAction();
        $action->setGateway($gatewayMock);
        $action->setApi($apiMock);
        
        $details = new ArrayObject([
            'merchantReference' => 'SomeReference',
        ]);
        
        $action->execute($notify = new Notify($details));
        $model = $notify->getModel();
        $this->assertSame(200, $model['response_status']);
        $this->assertSame('result', $model['authResult']);
    }
    
    /**
     * @return MockObject|Api
     */
    protected function createApiMock()
    {
        return $this->createMock(Api::class, ['merchantSig', 'verifyNotification'], [], '', false);
    }
    
    /**
     * @return MockObject|GatewayInterface
     */
    protected function createGatewayMock()
    {
        return $this->createMock(GatewayInterface::class);
    }
}
