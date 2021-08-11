<?php

declare(strict_types=1);

namespace Payum\Adyen;

//use Payum\Adyen\Action\AuthorizeAction;
//use Payum\Adyen\Action\CancelAction;
use Payum\Adyen\Action\ConvertPaymentAction;
use Payum\Adyen\Action\CaptureAction;
use Payum\Adyen\Action\NotifyAction;
//use Payum\Adyen\Action\RefundAction;
use Payum\Adyen\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class AdyenGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name' => 'adyen',
            'payum.factory_title' => 'Adyen',
        ]);

        $config->defaults([
            'payum.action.capture' => new CaptureAction(),
            'payum.action.notify' => new NotifyAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
            'payum.action.status' => new StatusAction(),
        ]);

        if (false === $config['payum.api']) {
            $config['payum.default_options'] = [
                'skinCode' => '',
                'merchantAccount' => '',
                'hmacKey' => '',
                'sandbox' => true,
                'notification_method' => 'basic',
                'default_payment_fields' => [],
            ];
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = [
                'skinCode',
                'merchantAccount',
                'hmacKey',
            ];

            $config['payum.api'] = static function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);

                return new Api(
                    [
                        'skinCode' => $config['skinCode'],
                        'merchantAccount' => $config['merchantAccount'],
                        'hmacKey' => $config['hmacKey'],
                        'sandbox' => $config['sandbox'],
                        'notification_method' => $config['notification_method'],
                        'default_payment_fields' => $config['default_payment_fields'],
                    ],
                    $config['payum.http_client']
                );
            };
        }
    }
}
