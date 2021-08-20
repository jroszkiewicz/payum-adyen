<?php

declare(strict_types=1);

namespace Payum\Adyen\Action;

use Payum\Core\Action\ActionInterface;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\RequestNotSupportedException;
use Payum\Core\GatewayAwareInterface;
use Payum\Core\GatewayAwareTrait;
use Payum\Core\Model\PaymentInterface;
use Payum\Core\Request\Convert;

class ConvertPaymentAction implements GatewayAwareInterface, ActionInterface
{
    use GatewayAwareTrait;
    
    /**
     * @param Convert $request
     */
    public function execute($request)
    {
        RequestNotSupportedException::assertSupports($this, $request);

        /**
         * @var PaymentInterface $payment
         */
        $payment = $request->getSource();

        $details = ArrayObject::ensureArrayObject($payment->getDetails());

        $details['merchantReference'] = $payment->getNumber();
        $details['paymentAmount'] = $payment->getTotalAmount();
        $details['shopperEmail'] = $payment->getClientEmail();
        $details['currencyCode'] = $payment->getCurrencyCode();
        $details['shopperReference'] = $payment->getClientId();

        $request->setResult((array) $details);
    }

    public function supports($request): bool
    {
        return
            $request instanceof Convert &&
            $request->getSource() instanceof PaymentInterface &&
            $request->getTo() === 'array'
        ;
    }
}
