<?php

declare(strict_types=1);

namespace Tests\Payum\Adyen\Action;

use Payum\Adyen\Action\CaptureAction;
use Payum\Adyen\Api;
use Payum\Core\ApiAwareInterface;
use Payum\Core\Exception\UnsupportedApiException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\Request\Capture;
use Payum\Core\Tests\GenericActionTest;
use PHPUnit\Framework\MockObject\MockObject;
use ReflectionClass;
use stdClass;

class CaptureActionTest extends GenericActionTest
{
    protected $actionClass = CaptureAction::class;
    
    protected $requestClass = Capture::class;
    
    public function testShouldBeSubClassOfGatewayAwareAction(): void
    {
        $rc = new ReflectionClass(CaptureAction::class);
        
        $this->assertTrue($rc->isSubclassOf(GatewayAwareInterface::class));
    }
    
    public function testShouldImplementApiAwareInterface(): void
    {
        $rc = new ReflectionClass(CaptureAction::class);
        
        $this->assertTrue($rc->implementsInterface(ApiAwareInterface::class));
    }
    
    public function testShouldAllowSetApi(): void
    {
        $expectedApi = $this->createApiMock();
        
        $action = new CaptureAction();
        $action->setApi($expectedApi);
        
        $this->assertAttributeSame($expectedApi, 'api', $action);
    }
    
    public function testThrowIfUnsupportedApiGiven(): void
    {
        $this->expectException(UnsupportedApiException::class);
        
        $action = new CaptureAction();
        
        $action->setApi(new stdClass());
    }
    
    /**
     * @return MockObject|Api
     */
    protected function createApiMock()
    {
        return $this->createMock(Api::class, [], [], '', false);
    }
}
