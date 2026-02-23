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
            ->subject('❌ Demande d\'accès refusée - '.config('app.name'))
            ->error()
            ->greeting('Votre demande d\'accès a été refusée.')
            ->line("Motif : {$this->adminMessage}")
            ->line('Vous pouvez soumettre une nouvelle demande avec une justification complémentaire si nécessaire.')
            ->action('Soumettre une nouvelle demande', route('access-request.create'))
            ->salutation('— '.config('app.name'));
    }
}
