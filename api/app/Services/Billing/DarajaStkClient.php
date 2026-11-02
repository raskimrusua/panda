<?php

namespace App\Services\Billing;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

/**
 * DarajaStkClient — thin HTTP wrapper around the Safaricom Daraja API.
 * Mirrors Shira's PlatformBillingService._get_mpesa_token + _send_stk_push.
 */
class DarajaStkClient
{
    public function token(): ?string
    {
        $consumerKey = (string) config('daraja.consumer_key');
        $consumerSecret = (string) config('daraja.consumer_secret');

        if ($consumerKey === '' || $consumerSecret === '') {
            Log::warning('[daraja] Platform credentials not configured');

            return null;
        }

        $authUrl = config('daraja.api_url').'/oauth/v1/generate?grant_type=client_credentials';

        try {
            $response = Http::timeout(30)
                ->withBasicAuth($consumerKey, $consumerSecret)
                ->get($authUrl);

            $response->throw();

            $token = $response->json('access_token');

            return is_string($token) && $token !== '' ? $token : null;
        } catch (Throwable $e) {
            Log::error('[daraja] Failed to get access token: '.$e->getMessage());

            return null;
        }
    }

    /**
     * @return array{success: bool, checkout_request_id?: string, message?: string}
     */
    public function stkPush(
        string $accessToken,
        string $phone,
        int $amount,
        string $accountReference,
        string $description,
    ): array {
        $shortcode = (string) config('daraja.shortcode');
        $passkey = (string) config('daraja.passkey');
        $callbackUrl = (string) config('daraja.subscription_callback_url');

        $timestamp = now()->format('YmdHis');
        $password = base64_encode($shortcode.$passkey.$timestamp);

        $payload = [
            'BusinessShortCode' => $shortcode,
            'Password' => $password,
            'Timestamp' => $timestamp,
            'TransactionType' => 'CustomerPayBillOnline',
            'Amount' => $amount,
            'PartyA' => $phone,
            'PartyB' => $shortcode,
            'PhoneNumber' => $phone,
            'CallBackURL' => $callbackUrl,
            'AccountReference' => $accountReference,
            'TransactionDesc' => $description,
        ];

        try {
            $response = Http::timeout(30)
                ->withToken($accessToken)
                ->post(config('daraja.api_url').'/mpesa/stkpush/v1/processrequest', $payload);

            $data = $response->json() ?? [];

            if (($data['ResponseCode'] ?? '') === '0') {
                return [
                    'success' => true,
                    'checkout_request_id' => (string) ($data['CheckoutRequestID'] ?? ''),
                ];
            }

            return [
                'success' => false,
                'message' => (string) ($data['ResponseDescription'] ?? 'STK Push failed'),
            ];
        } catch (Throwable $e) {
            Log::error('[daraja] STK push request failed: '.$e->getMessage());

            return ['success' => false, 'message' => $e->getMessage()];
        }
    }
}
