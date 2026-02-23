<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class AccessRequestRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public AccessRequest $accessRequest,
        public string $adminMessage
    ) {}

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('❌ Access Request Rejected - '.config('app.name'))
            ->error()
            ->greeting('Your access request has been rejected.')
            ->line("Reason: {$this->adminMessage}")
            ->line('You may submit a new request with additional justification if needed.')
            ->action('Submit a new request', route('access-request.create'))
            ->salutation('— '.config('app.name'));
    }
}
