<?php

declare(strict_types=1);

namespace App\Notifications;

use App\Models\AccessRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class NewAccessRequestNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(public AccessRequest $accessRequest)
    {
        $this->onQueue('notifications');
    }

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('📋 Nouvelle demande d\'accès - '.config('app.name'))
            ->greeting('Nouvelle demande d\'accès reçue')
            ->line("Un utilisateur a demandé l'accès à l'application.")
            ->line("**Kerberos :** `{$this->accessRequest->kerberos}`")
            ->line("**Justification :** {$this->accessRequest->justification}")
            ->salutation('— '.config('app.name'));
    }

    /** @return array<string, mixed> */
    public function toArray(object $notifiable): array
    {
        return [
            'access_request_id' => $this->accessRequest->id,
            'kerberos' => $this->accessRequest->kerberos,
            'user_name' => $this->accessRequest->user?->name,
            'justification' => $this->accessRequest->justification,
            'created_at' => $this->accessRequest->created_at->toISOString(),
        ];
    }
}
