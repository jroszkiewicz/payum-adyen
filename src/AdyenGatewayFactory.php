<?php

declare(strict_types=1);

namespace Payum\Adyen;

use Payum\Adyen\Action\ConvertPaymentAction;
use Payum\Adyen\Action\CaptureAction;
use Payum\Adyen\Action\NotifyAction;
use Payum\Adyen\Action\StatusAction;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\GatewayFactory;

class AdyenGatewayFactory extends GatewayFactory
{
    protected function populateConfig(ArrayObject $config): void
    {
        $config->defaults([
            'payum.factory_name'  => 'adyen',
            'payum.factory_title' => 'Adyen',
        ]);
        
        $config->defaults([
            'payum.action.capture'         => new CaptureAction(),
            'payum.action.notify'          => new NotifyAction(),
            'payum.action.convert_payment' => new ConvertPaymentAction(),
            'payum.action.status'          => new StatusAction(),
        ]);
        
        if (null === $config['payum.api']) {
            $config['payum.default_options'] = [
                'apiKey'                 => '',
                'merchantAccount'        => '',
                'sandbox'                => true,
                'default_payment_fields' => [],
            ];
            $config->defaults($config['payum.default_options']);
            $config['payum.required_options'] = [
                'apiKey',
            ];
            
            $config['payum.api'] = static function (ArrayObject $config) {
                $config->validateNotEmpty($config['payum.required_options']);
                
                return new Api(
                    [
                        'apiKey'                 => $config['apiKey'],
                        'merchantAccount'        => $config['merchantAccount'],
                        'sandbox'                => $config['sandbox'],
                        'default_payment_fields' => $config['default_payment_fields'],
                    ]
                );
            };
        }
    }
}
