<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class AdminNotificationService
{
    /** @param array<string, mixed> $opts reserved for future flags */
    public static function send(string $subject, string $bodyHtml, array $opts = []): void
    {
        $apiKey = config('services.resend.api_key');
        $adminEmail = config('services.resend.admin_email', config('mail.from.address'));

        if (! $apiKey) {
            Log::warning('AdminNotificationService: RESEND_API_KEY not set', ['subject' => $subject]);

            return;
        }

        try {
            Http::withHeaders([
                'Authorization' => 'Bearer '.$apiKey,
                'Content-Type' => 'application/json',
            ])->post('https://api.resend.com/emails', [
                'from' => config('mail.from.address', 'hello@panda.shira.farm'),
                'to' => $adminEmail,
                'subject' => '[Panda] '.$subject,
                'html' => $bodyHtml,
            ]);
        } catch (\Throwable $e) {
            Log::error('AdminNotificationService: dispatch failed: '.$e->getMessage());
        }
    }
}
