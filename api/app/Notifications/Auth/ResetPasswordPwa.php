<?php

namespace App\Notifications\Auth;

use Illuminate\Auth\Notifications\ResetPassword;

/**
 * Same flow as Laravel's default ResetPassword notification but the link
 * points at the PWA frontend. PWA collects the new password from the
 * user and posts `{email, token, password, password_confirmation}` to
 * `POST /api/v1/auth/password/reset`.
 */
class ResetPasswordPwa extends ResetPassword
{
    protected function resetUrl($notifiable): string
    {
        return rtrim((string) config('app.frontend_url'), '/').'/reset-password?'.http_build_query([
            'token' => $this->token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
