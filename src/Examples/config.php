<?php

$autoload = is_file(__DIR__ . '/../vendor/autoload.php') ? __DIR__ . '/../vendor/autoload.php' : __DIR__.'/../../../../vendor/autoload.php';

require_once $autoload;

use Payum\Adyen\AdyenGatewayFactory;
use Payum\Core\GatewayFactoryInterface;
use Payum\Core\Model\Payment;
use Payum\Core\PayumBuilder;

$paymentClass = Payment::class;
$gatewayName = 'adyen';

$defaultConfig = [
    'factory' => $gatewayName,
    'sandbox' => true,
    // Spec
    'skinCode' => '',
    'merchantAccount' => '',
    'hmacKey' => '',
];

$builder = new PayumBuilder();
$builder
    ->addGatewayFactory($gatewayName, static function(array $config, GatewayFactoryInterface $coreGatewayFactory) {
        return new AdyenGatewayFactory($config, $coreGatewayFactory);
    });
$builder
    ->addGateway($gatewayName, $defaultConfig);
$builder
    ->addDefaultStorages();

$builder
    ->setGenericTokenFactoryPaths([
        'capture' => 'Examples/capture.php',
        'notify' => 'Examples/notify.php',
    ]);

$payum = $builder
    ->getPayum();
