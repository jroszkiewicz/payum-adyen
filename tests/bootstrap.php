<?php

use Payum\Core\GatewayInterface;

if (!$loader = @require __DIR__.'/../vendor/autoload.php') {
    echo <<<EOM
You must set up the project dependencies by running the following commands:

    curl -s http://getcomposer.org/installer | php
    php composer.phar install --dev

EOM;

    exit(1);
}

$rc = new ReflectionClass(GatewayInterface::class);
$coreDir = dirname($rc->getFileName()).'/tests';

$loader->add('Payum\Core\Tests', $coreDir);
$loader->add('Payum\Adyen\Tests', __DIR__);
