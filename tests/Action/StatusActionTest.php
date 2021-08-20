<?php

declare(strict_types=1);

namespace Tests\Payum\Adyen\Action;

use Payum\Adyen\Action\StatusAction;
use Payum\Core\Request\GetHumanStatus;
use Payum\Core\Tests\GenericActionTest;

class StatusActionTest extends GenericActionTest
{
    protected $actionClass = StatusAction::class;
    
    protected $requestClass = GetHumanStatus::class;
    
    public function testShouldMarkNewIfDetailsEmpty(): void
    {
        $action = new StatusAction();
        
        $action->execute($status = new GetHumanStatus([]));
        
        self::assertTrue($status->isNew());
    }
    
    public function testShouldMarkFailedIfResponseStatusIsFailed(): void
    {
        $action = new StatusAction();
        
        $action->execute($status = new GetHumanStatus([
            'authResult'      => 'Something',
            'response_status' => 400,
        ]));
        
        self::assertTrue($status->isFailed());
    }
    
    public function testShouldMarkAuthorizedIfResponseStatusIsOkAndAuthResultIsAuthorised(): void
    {
        $action = new StatusAction();
        
        $action->execute($status = new GetHumanStatus([
            'authResult'      => 'AUTHORISED',
            'response_status' => 200,
        ]));
        
        self::assertTrue($status->isAuthorized());
    }
    
    public function testShouldMarkNewIfAuthResultIsNull(): void
    {
        $action = new StatusAction();
        
        $action->execute($status = new GetHumanStatus([
            'authResult' => null,
        ]));
        
        self::assertTrue($status->isNew());
    }
    
    public function testShouldMarkAuthorizedIfAuthResultIsAuthorized(): void
    {
        $action = new StatusAction();
        
        $action->execute($status = new GetHumanStatus([
            'authResult' => 'AUTHORISED',
        ]));
        
        self::assertTrue($status->isAuthorized());
    }
    
    public function testShouldMarkPendindIfAuthResultIsPending(): void
    {
        $action = new StatusAction();
        
        $action->execute($status = new GetHumanStatus([
            'authResult' => 'PENDING',
        ]));
        
        self::assertTrue($status->isPending());
    }
    
    public function testShouldMarkCaptureIfAuthResultIsCapture(): void
    {
        $action = new StatusAction();
        
        $action->execute($status = new GetHumanStatus([
            'authResult' => 'CAPTURE',
        ]));
        
        self::assertTrue($status->isCaptured());
    }
    
    public function testShouldMarkCanceledIfAuthResultIsCanceled(): void
    {
        $action = new StatusAction();
        
        $action->execute($status = new GetHumanStatus([
            'authResult' => 'CANCELLED',
        ]));
        
        self::assertTrue($status->isCanceled());
    }
    
    public function testShouldMarkFaildeIfAuthResultIsRefused(): void
    {
        $action = new StatusAction();
        
        $action->execute($status = new GetHumanStatus([
            'authResult' => 'REFUSED',
        ]));
        
        self::assertTrue($status->isFailed());
    }
    
    public function testShouldMarkSuspendedIfAuthResultIsChargeback(): void
    {
        $action = new StatusAction();
        
        $action->execute($status = new GetHumanStatus([
            'authResult' => 'CHARGEBACK',
        ]));
        
        self::assertTrue($status->isSuspended());
    }
    
    public function testShouldMarkExpiredIfAuthResultIsExpire(): void
    {
        $action = new StatusAction();
        
        $action->execute($status = new GetHumanStatus([
            'authResult' => 'EXPIRE',
        ]));
        
        self::assertTrue($status->isExpired());
    }
    
    public function testShouldMarkRefundedIfAuthResultIsRefund(): void
    {
        $action = new StatusAction();
        
        $action->execute($status = new GetHumanStatus([
            'authResult' => 'REFUND',
        ]));
        
        self::assertTrue($status->isRefunded());
    }
    
    public function testShouldMarkFailedIfAuthResultIsError(): void
    {
        $action = new StatusAction();
        
        $action->execute($status = new GetHumanStatus([
            'authResult' => 'ERROR',
        ]));
        
        self::assertTrue($status->isFailed());
    }
    
    public function testShouldMarkUnknownIfAuthResultIsUnknown(): void
    {
        $action = new StatusAction();
        
        $action->execute($status = new GetHumanStatus([
            'authResult' => 'SomeStatus',
        ]));
        
        self::assertTrue($status->isUnknown());
    }
}
