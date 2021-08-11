<?php

declare(strict_types=1);

namespace Payum\Adyen;

use GuzzleHttp\Psr7\Request;
use Payum\Core\Bridge\Guzzle\HttpClientFactory;
use Payum\Core\Bridge\Spl\ArrayObject;
use Payum\Core\Exception\Http\HttpException;
use Payum\Core\Exception\InvalidArgumentException;
use Payum\Core\Exception\LogicException;
use Payum\Core\HttpClientInterface;
use Psr\Http\Message\ResponseInterface;

class Api
{
    /**
     * @var array
     */
    protected $requiredFields = [
        'merchantReference' => null,
        'paymentAmount' => null,
        'currencyCode' => null,
        'shipBeforeDate' => null,
        'skinCode' => null,
        'merchantAccount' => null,
        'sessionValidity' => null,
        'shopperEmail' => null,
    ];
    /**
     * @var array
     */
    protected $optionalFields = [
        'merchantReturnData' => null,
        'shopperReference' => null,
        'allowedMethods' => null,
        'blockedMethods' => null,
        'offset' => null,
        'shopperStatement' => null,
        'recurringContract' => null,
        'billingAddressType' => null,
        'deliveryAddressType' => null,

        'resURL' => null,
    ];
    /**
     * @var array
     */
    protected $othersFields = [
        'brandCode' => null,
        'countryCode' => null,
        'shopperLocale' => null,
        'orderData' => null,
        'offerEmail' => null,

        'issuerId' => null,
    ];
    /**
     * @var array
     */
    protected $responseFields = [
        'authResult' => null,
        'pspReference' => null,
        'merchantReference' => null,
        'skinCode' => null,
        'paymentMethod' => null,
        'shopperLocale' => null,
        'merchantReturnData' => null,
    ];
    /**
     * @var array
     */
    protected $notificationFields = [
        'pspReference' => null,
        'originalReference' => null,
        'merchantAccountCode' => null,
        'merchantReference' => null,
        'amount.value' => null,
        'amount.currency' => null,
        'eventCode' => null,
        'success' => null,
    ];

    /**
     * @var HttpClientInterface
     */
    protected $client;

    /**
     * @var array
     */
    protected $options = [
        'skinCode' => null,
        'merchantAccount' => null,
        'hmacKey' => null,
        'sandbox' => null,
        'notification_method' => null,
        'notification_hmac' => null,
        // List of values getting from conf
        'default_payment_fields' => [],
    ];

    /**
     * @param array               $options
     * @param HttpClientInterface $client
     *
     * @throws InvalidArgumentException if an option is invalid
     * @throws LogicException if a sandbox is not boolean
     */
    public function __construct(array $options, HttpClientInterface $client = null)
    {
        $options = ArrayObject::ensureArrayObject($options);
        $options->defaults($this->options);
        $options->validateNotEmpty([
            'skinCode',
            'merchantAccount',
            'hmacKey',
        ]);

        if (false === is_bool($options['sandbox'])) {
            throw new LogicException('The boolean sandbox option must be set.');
        }
        $this->options = $options;
        $this->client = $client ?: HttpClientFactory::create();
    }

    /**
     * @return string
     */
    public function getApiEndpoint(): string
    {
        return sprintf('https://%s.adyen.com/hpp/select.shtml', $this->options['sandbox'] ? 'test' : 'live');
    }

    public function merchantSig(array $params, array $keys = null, string $hmacKey = 'hmacKey'): string
    {
        $keys = $keys ?: array_merge(array_keys($this->requiredFields), array_keys($this->optionalFields));

        // Sign only not empty fields
        $data = [];
        
        foreach ($keys as $key) {
            if (isset($params[$key])) {
                $data[$key] = $params[$key];
            }
        }
        
        $params = array_filter($data);

        // The character escape function
        $escape = static function($val) {
            return str_replace(['\\', ':'], ['\\\\', '\\:'], $val);
        };

        // Sort the array by key using SORT_STRING order
        ksort($params, SORT_STRING);

        // Generate the signing data string
        $signData = implode(":", array_map($escape, array_merge(array_keys($params), array_values($params))));

        // base64-encode the binary result of the HMAC computation
        return base64_encode(hash_hmac('sha256', $signData, pack("H*", $this->options[$hmacKey]), true));
    }

    public function verifySign(array $params): bool
    {
        if (empty($params['merchantSig'])) {
            return false;
        }
        
        $merchantSig = $params['merchantSig'];

        return $merchantSig === $this->merchantSig($params);
    }
    
    public function verifyResponse(array $params): bool
    {
        if (empty($params['merchantSig'])) {
            return false;
        }
        
        $merchantSig = $params['merchantSig'];

        return $merchantSig === $this->merchantSig($params, array_keys($this->responseFields), 'notification_hmac');
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
            $params = array_merge($params, (array) $this->options['default_payment_fields']);
        }

        $params['shipBeforeDate'] = date('Y-m-d', strtotime('+1 hour'));
        $params['sessionValidity'] = date(DATE_ATOM, strtotime('+1 hour'));

        $params['skinCode'] = $this->options['skinCode'];
        $params['merchantAccount'] = $this->options['merchantAccount'];

        $supportedParams = array_merge($this->requiredFields, $this->optionalFields, $this->othersFields);

        $params = array_filter(array_replace(
            $supportedParams,
            array_intersect_key($params, $supportedParams)
        ));

        $params['merchantSig'] = $this->merchantSig($params);

        return $params;
    }

    /**
     * @param string $id
     * @param float  $amount
     * @param string $currency
     * @param string $customerId
     * @param string $customerEmail
     * @param string $returnUrl
     * @param string $method
     *
     * @return array
     */
    public function startHostedPaymentPages($id, $amount, $currency, $customerId, $customerEmail, $returnUrl, $method): array
    {
        $params = [
            // Required
            'merchantReference' => $id,
            'paymentAmount' => $amount * 100,
            'currencyCode' => $currency,
            'shipBeforeDate' => date('Y-m-d', strtotime('+1 hour')),
            'skinCode' => $this->options['skinCode'],
            'merchantAccount' => $this->options['merchantAccount'],
            'sessionValidity' => date(DATE_ATOM, strtotime('+1 hour')),
            'shopperEmail' => $customerEmail,
            // Optional
            'shopperReference' => $customerId,
            'resURL' => $returnUrl,
        ];

        switch ($method) {
            case 'adyen_mister_cash':
                $params['allowedMethods'] = 'bcmc';
                $params['countryCode'] = 'BE';
                break;

            case 'adyen_direct_ebanking':
                $params['allowedMethods'] = 'directEbanking';
                $params['countryCode'] = 'DE';
                break;

            case 'adyen_giropay':
                $params['allowedMethods'] = 'giropay';
                $params['countryCode'] = 'DE';
                break;

            case 'adyen_credit_card':
                $params['allowedMethods'] = 'amex,visa,mc';
                break;
        }

        $params = $this->prepareFields($params);

        return $params;
    }

    /**
     * @param array  $fields
     *
     * @return ResponseInterface
     * @throws HttpException
     */
    protected function doRequest(array $fields): ResponseInterface
    {
        $headers = [
            'Content-Type' => 'application/x-www-form-urlencoded',
        ];

        $request = new Request('POST', $this->getApiEndpoint(), $headers, http_build_query($fields));

        $response = $this->client->send($request);

        if (false === ($response->getStatusCode() >= 200 && $response->getStatusCode() < 300)) {
            throw HttpException::factory($request, $response);
        }

        // Check response
        return $response->getBody()->getContents();
    }
}
