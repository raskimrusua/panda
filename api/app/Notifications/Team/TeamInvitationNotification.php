<?php

namespace App\Notifications\Team;

use App\Models\TeamInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Sent to the invitee on `POST /team/invite`. The accept link points at
 * the PWA `/accept-invite?token=...` which collects name + password
 * from the user and POSTs to `/team/accept/{token}`.
 */
class TeamInvitationNotification extends Notification
{
    use Queueable;

    public function __construct(
        private readonly TeamInvitation $invitation,
        private readonly string $inviterName,
        private readonly string $tenantName,
    ) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $frontend = rtrim((string) config('app.frontend_url'), '/');
        $acceptUrl = "{$frontend}/accept-invite?".http_build_query([
            'token' => $this->invitation->token,
        ]);

        $expiresOn = $this->invitation->expires_at->toFormattedDateString();

        return (new MailMessage)
            ->subject("Join {$this->tenantName} on Panda")
            ->greeting('Hello,')
            ->line("{$this->inviterName} has invited you to join {$this->tenantName} on Panda.")
            ->action('Accept invitation', $acceptUrl)
            ->line("This invitation expires on {$expiresOn}.")
            ->line('If you were not expecting this email, you can ignore it.');
    }
}
