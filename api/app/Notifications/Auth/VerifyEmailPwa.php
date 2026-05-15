<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\VerifyEmail;

/**
 * Email-verification notification. Reuses Laravel's default — the signed
 * link points at the API verify endpoint (route name `verification.verify`).
 * That endpoint marks `email_verified_at`, then issues a 302 redirect to
 * `{FRONTEND_URL}/verified` (or `?error=...` on failure) so the user
 * lands back in the PWA.
 *
 * Kept as a wrapper class so subsequent customisation (subject line,
 * branded HTML template, Swahili translation) lives in app code rather
 * than the framework.
 */
class VerifyEmailPwa extends VerifyEmail
{
    //
}
