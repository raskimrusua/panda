<?php

namespace App\Http\Controllers\Api\V1\Billing;

use App\Http\Controllers\Controller;
use App\Services\Billing\DarajaStkClient;
use App\Services\Billing\SubscriptionBillingService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/**
 * Public STK callback sink — Safaricom POSTs here after every STK push.
 *
 * No auth middleware: Daraja callbacks are server-to-server and carry no
 * tenant context; matching happens by CheckoutRequestID (a random value we
 * issued). The service never throws, and Safaricom retries non-2xx — so the
 * response is always 200, mirroring Shira's callback behaviour.
 */
class MpesaCallbackController extends Controller
{
    public function stkCallback(Request $request): JsonResponse
    {
        $payload = $request->json()->all();

        if (is_array($payload)) {
            (new SubscriptionBillingService(new DarajaStkClient))->processStkCallback($payload);
        }

        return response()->json(['ResultCode' => 0, 'ResultDesc' => 'Accepted']);
    }
}
