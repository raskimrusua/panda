<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Reconsent gate — Kenya DPA 2019 §31 requires consent from every existing
 * data subject when the lawful-basis document changes, not just new
 * signups. This middleware compares the authenticated user's stored
 * terms_version + privacy_version against the current config('legal.*')
 * values; on divergence it short-circuits with 409 TERMS_VERSION_OUTDATED.
 *
 * Applied at route-group level on tenant-scoped endpoints. Whitelisted
 * paths (auth, policies, health) skip the gate so a stale user can still
 * reach the /policies/accept endpoint that resolves the divergence — a
 * gate that gated its own escape would brick the account.
 *
 * Envelope shape mirrors Shira's apps/core/middleware/consent_gate.py
 * exactly so frontend interceptors can be ported one-to-one between
 * the two products. Code = 'TERMS_VERSION_OUTDATED'.
 */
class ConsentGate
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        if ($user === null) {
            return $next($request);
        }

        $termsV = (string) config('legal.terms_version');
        $privacyV = (string) config('legal.privacy_version');

        $staleTerms = ((string) ($user->terms_version ?? '')) !== $termsV;
        $stalePrivacy = ((string) ($user->privacy_version ?? '')) !== $privacyV;

        if (! $staleTerms && ! $stalePrivacy) {
            return $next($request);
        }

        return new JsonResponse(
            [
                'detail' => 'You must accept the latest Terms of Service and Privacy Policy.',
                'code' => 'TERMS_VERSION_OUTDATED',
                'required' => [
                    'terms_version' => $termsV,
                    'privacy_version' => $privacyV,
                ],
                'current' => [
                    'terms_version' => $user->terms_version ?: null,
                    'privacy_version' => $user->privacy_version ?: null,
                ],
            ],
            Response::HTTP_CONFLICT,
        );
    }
}
