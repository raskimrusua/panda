<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\AcceptPoliciesRequest;
use App\Models\UserConsent;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Current policy versions + reconsent endpoint.
 *
 * Kenya DPA 2019 §30 — the controller must demonstrate consent for each
 * version of each policy. /policies/active is unauthenticated (the PWA
 * shows it on the public marketing flow and at /accept-terms); /policies/accept
 * writes UserConsent rows + stamps the User flat columns.
 *
 * Both routes are whitelisted from the ConsentGate middleware so a stale
 * user can still escape via /policies/accept; gating the gate's own escape
 * would brick the account.
 */
class PolicyController extends Controller
{
    public function active(): JsonResponse
    {
        $marketing = rtrim((string) config('legal.marketing_url'), '/');

        return new JsonResponse([
            'terms' => [
                'version' => (string) config('legal.terms_version'),
                'url' => "{$marketing}/terms",
            ],
            'privacy' => [
                'version' => (string) config('legal.privacy_version'),
                'url' => "{$marketing}/privacy",
            ],
        ]);
    }

    public function accept(AcceptPoliciesRequest $request): JsonResponse
    {
        $user = $request->user();
        $ip = $request->ip();
        $ua = mb_substr((string) $request->userAgent(), 0, 2000);
        $now = Carbon::now();
        $termsV = (string) config('legal.terms_version');
        $privacyV = (string) config('legal.privacy_version');

        DB::transaction(function () use ($user, $ip, $ua, $now, $termsV, $privacyV) {
            // Idempotent on the unique (user_id, policy_type, version) constraint —
            // a double-tap from a flaky PWA connection is safe.
            UserConsent::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'policy_type' => UserConsent::POLICY_TERMS,
                    'version' => $termsV,
                ],
                [
                    'ip_address' => $ip,
                    'user_agent' => $ua,
                    'accepted_at' => $now,
                ],
            );
            UserConsent::firstOrCreate(
                [
                    'user_id' => $user->id,
                    'policy_type' => UserConsent::POLICY_PRIVACY,
                    'version' => $privacyV,
                ],
                [
                    'ip_address' => $ip,
                    'user_agent' => $ua,
                    'accepted_at' => $now,
                ],
            );

            $user->forceFill([
                'terms_accepted_at' => $now,
                'terms_version' => $termsV,
                'privacy_accepted_at' => $now,
                'privacy_version' => $privacyV,
            ])->save();
        });

        return new JsonResponse([
            'terms_version' => $termsV,
            'privacy_version' => $privacyV,
            'accepted_at' => $now->toIso8601String(),
        ]);
    }
}
