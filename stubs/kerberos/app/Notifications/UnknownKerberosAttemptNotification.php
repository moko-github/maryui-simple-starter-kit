<?php

declare(strict_types=1);

namespace App\Notifications;

use Carbon\Carbon;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class UnknownKerberosAttemptNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public string $kerberos,
        public string $ipAddress,
        public string $userAgent,
        public Carbon $attemptedAt
    ) {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('⚠️ Unknown Kerberos Login Attempt - '.config('app.name'))
            ->error()
            ->greeting('Unknown Kerberos Identifier Detected')
            ->line("An authentication attempt was made with an unrecognised Kerberos identifier.")
            ->line("**Identifier:** `{$this->kerberos}`")
            ->line("**IP Address:** {$this->ipAddress}")
            ->line("**Browser:** {$this->userAgent}")
            ->line("**Date/Time:** {$this->attemptedAt->format('d/m/Y H:i:s')}")
            ->line('If this is a legitimate user, add their Kerberos identifier to the system.')
            ->salutation('— '.config('app.name'));
    }
}
