<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class PaystackService
{
    protected string $secretKey;
    protected string $baseUrl;

    public function __construct()
    {
        $this->secretKey = config('services.paystack.secret');
        $this->baseUrl = config('services.paystack.base_url', 'https://api.paystack.co');
    }

    /**
     * Initialize a payment and return Paystack authorization URL
     */
    public function initializeTransaction(string $email, float $amount, string $callbackUrl): array
    {
        $response = Http::withToken($this->secretKey)->post("{$this->baseUrl}/transaction/initialize", [
            'email' => $email,
            'amount' => $amount * 100, // Paystack uses kobo
            'currency' => 'NGN',
            'callback_url' => $callbackUrl,
        ]);

        if ($response->successful() && isset($response['data']['authorization_url'])) {
            return $response['data'];
        }

        throw new \Exception('Failed to initialize Paystack payment: ' . $response->body());
    }
    public function verifyTransaction(string $reference): array
{
    $response = Http::withToken($this->secretKey)
        ->get("{$this->baseUrl}/transaction/verify/{$reference}");

    if ($response->successful() && isset($response['data'])) {
        return $response['data'];
    }

    throw new \Exception('Failed to verify Paystack payment: ' . $response->body());
}
}
