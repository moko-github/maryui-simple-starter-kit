<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccessRequestAcceptedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AccessRequest $accessRequest,
        public ?string $adminMessage = null
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        $roleName = $this->accessRequest->processedBy?->role?->name ?? 'User';

        $mail = (new MailMessage)
            ->subject('✅ Access Request Approved - '.config('app.name'))
            ->success()
            ->greeting('Your access request has been approved!')
            ->line("Role assigned: **{$roleName}**")
            ->action('Log in now', route('login'));

        if ($this->adminMessage) {
            $mail->line("Message from the administrator: {$this->adminMessage}");
        }

        return $mail->salutation('— '.config('app.name'));
    }
}
