<?php
namespace App\Services;

use GuzzleHttp\Client;

class FawryService
{
    protected $client;
    protected $merchantCode;
    protected $securityKey;
    protected $baseUrl;

    public function __construct()
    {
        $this->client       = new Client();
        $this->merchantCode = config('services.fawry.merchant_code');
        $this->securityKey  = config('services.fawry.secure_key');
        $this->baseUrl      = config('services.fawry.base_url');
    }

    /**
     * Format amount to 2 decimal places
     */
    public function fmtAmount($amount): string
    {
        return number_format((float) $amount, 2, '.', '');
    }

    /**
     * 🔹 Reference signature
     */
    public function makeReferenceSignature(
         $merchantRefNum,
         $customerProfileId,
         $paymentMethod,
         $amount
    ){
        $data = $this->merchantCode
        . $merchantRefNum
        . $customerProfileId
        . $paymentMethod
        . $amount
        . $this->securityKey;

        return hash('sha256', $data);
    }

    public function createReferenceCharge(array $payload): array
    {
        $url  = $this->baseUrl . '/ECommerceWeb/Fawry/payments/charge';
        $resp = $this->client->post($url, [
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json'    => $payload,
            'timeout' => 30,
        ]);

        return json_decode($resp->getBody()->getContents(), true);
    }

    /**
     * 🔹 Card 3DS signature
     */
    public function make3DSCardSignature(
         $merchantRefNum,
         $customerProfileId,
         $paymentMethod,
         $amount,
         $cardNumber,
         $expiryYear,
         $expiryMonth,
         $cvv,
         $returnUrl
    ) {
        $data = $this->merchantCode
        . $merchantRefNum
        . $customerProfileId
        . $paymentMethod
        . $amount
        . $cardNumber
        . $expiryYear
        . $expiryMonth
        . $cvv
        . $returnUrl
        . $this->securityKey;

        return hash('sha256', $data);
    }

    public function create3DSCardCharge(array $payload): array
    {
        $url  = $this->baseUrl . '/ECommerceWeb/Fawry/payments/charge';
        $resp = $this->client->post($url, [
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json'    => $payload,
            'timeout' => 30,
        ]);

        return json_decode($resp->getBody()->getContents(), true);
    }

    /**
     * 🔹 Wallet signature
     */
    public function makeWalletSignature(
         $merchantRefNum,
         $customerProfileId,
         $paymentMethod,
         $amount,
         $walletMobile
    ) {
        $data = $this->merchantCode
        . $merchantRefNum
        . $customerProfileId
        . $paymentMethod
        . $amount
        . $walletMobile
        . $this->securityKey;

        return hash('sha256', $data);
    }

    public function createWalletCharge(array $payload): array
    {
        $url  = $this->baseUrl . '/ECommerceWeb/Fawry/payments/charge';
        $resp = $this->client->post($url, [
            'headers' => [
                'Accept'       => 'application/json',
                'Content-Type' => 'application/json',
            ],
            'json'    => $payload,
            'timeout' => 30,
        ]);

        return json_decode($resp->getBody()->getContents(), true);
    }

    public function verifyWebhookSignature(array $data): bool
    {
        $merchantCode = config('services.fawry.merchant_code');
        $securityKey  = config('services.fawry.secure_key');

        $stringToHash = $merchantCode
            . ($data['orderAmount'] ?? '')
            . ($data['fawryRefNumber'] ?? '')
            . ($data['merchantRefNumber'] ?? '')
            . ($data['orderStatus'] ?? '')
            . ($data['paymentMethod'] ?? '')
            . ($data['paymentRefrenceNumber'] ?? '')
            . $securityKey;

        $expectedSignature = hash('sha256', $stringToHash);

        return strtolower($expectedSignature) === strtolower($data['signature'] ?? '');
    }
}
