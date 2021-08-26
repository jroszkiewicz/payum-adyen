<?php

declare(strict_types=1);

namespace Payum\Adyen;

use Adyen\Client;
use Adyen\Environment;
use Adyen\Service\Checkout;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\Exception\LogicException;

class Api
{
    /**
     * @var array
     */
    protected array $requiredFields = [
        'merchantReference' => null,
        'paymentAmount'     => null,
        'currencyCode'      => null,
        'shipBeforeDate'    => null,
        'skinCode'          => null,
        'merchantAccount'   => null,
        'sessionValidity'   => null,
        'shopperEmail'      => null,
    ];
    
    /**
     * @var array
     */
    protected array $optionalFields = [
        'merchantReturnData'  => null,
        'shopperReference'    => null,
        'allowedMethods'      => null,
        'blockedMethods'      => null,
        'offset'              => null,
        'shopperStatement'    => null,
        'recurringContract'   => null,
        'billingAddressType'  => null,
        'deliveryAddressType' => null,
        'resURL' => null,
    ];
    
    /**
     * @var array
     */
    protected array $othersFields = [
        'brandCode'     => null,
        'countryCode'   => null,
        'shopperLocale' => null,
        'orderData'     => null,
        'offerEmail'    => null,
        
        'issuerId' => null,
    ];
    
    /**
     * @var array
     */
    protected array $responseFields = [
        'authResult'         => null,
        'pspReference'       => null,
        'merchantReference'  => null,
        'skinCode'           => null,
        'paymentMethod'      => null,
        'shopperLocale'      => null,
        'merchantReturnData' => null,
    ];
    
    /**
     * @var array
     */
    protected array $notificationFields = [
        'pspReference'        => null,
        'originalReference'   => null,
        'merchantAccountCode' => null,
        'merchantReference'   => null,
        'amount.value'        => null,
        'amount.currency'     => null,
        'eventCode'           => null,
        'success'             => null,
    ];
    
    protected Client $client;
    
    /**
     * @var array
     */
    protected $options = [
        'apiKey'               => null,
        'sandbox'                => null,
        // List of values getting from conf
        'default_payment_fields' => [],
    ];
    
    /**
     * @param array               $options
     *
     * @throws InvalidArgumentException if an option is invalid
     * @throws LogicException if a sandbox is not boolean
     */
    public function __construct(array $options)
    {
        $arrayObject = ArrayObject::ensureArrayObject($options);
        $arrayObject->defaults($this->options);
        $arrayObject->validateNotEmpty([
            'apiKey'
        ]);
        
        if (false === is_bool($arrayObject['sandbox'])) {
            throw new LogicException('The boolean sandbox option must be set.');
        }
        
        $this->options = $arrayObject;
    
        $this->client = new Client();
    
        $this->client->setXApiKey($arrayObject['apiKey']);
        $this->client->setEnvironment($arrayObject['sandbox'] === true ? Environment::TEST : Environment::LIVE);
        $this->client->setTimeout(30);
    }
    
    public function verifyNotification(array $params): bool
    {
        if (('basic' === $this->options['notification_method']) || (null === $this->options['notification_hmac'])) {
            return true;
        }
        
        if (empty($params['additionalData.hmacSignature'])) {
            return false;
        }
        
        $merchantSig = $params['additionalData.hmacSignature'];
        
        return $merchantSig === $this->merchantSig($params, array_keys($this->notificationFields));
    }
    
    public function prepareFields(array $params): array
    {
        if (false !== empty($this->options['default_payment_fields'])) {
            $params = array_merge($params, (array)$this->options['default_payment_fields']);
        }
        
        $params['shipBeforeDate']  = date('Y-m-d', strtotime('+1 hour'));
        $params['sessionValidity'] = date(DATE_ATOM, strtotime('+1 hour'));
        
        $supportedParams = array_merge($this->requiredFields, $this->optionalFields, $this->othersFields);
        
        $params = array_filter(array_replace(
            $supportedParams,
            array_intersect_key($params, $supportedParams)
        ));
        
        return $params;
    }
    
    public function checkout(ArrayObject $model)
    {
        $json = '{
        "reference": "' . $model['merchantReference'] . '",
          "amount": {
            "value": ' . $model['paymentAmount'] . ',
            "currency": "' . $model['currencyCode'] . '"
          },
          "shopperReference": "' . $model['shopperReference'] . '",
          "description": "' . $model['description'] . '",
          "countryCode": "' . $model['countryCode'] . '",
          "merchantAccount": "' . $this->options['merchantAccount'] . '",
          "shopperLocale": "' . $model['locale'] . '"
        }';
        
        $service = new Checkout($this->client);
        $params  = json_decode($json, true);
    
        return $service->paymentLinks($params);
    }
}
