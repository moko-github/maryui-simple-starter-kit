<?php

declare(strict_types=1);

namespace MokoGithub\KerberosAuth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;
use MokoGithub\KerberosAuth\Models\AccessRequest;

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
            ->subject('✅ Demande d\'accès approuvée - '.config('app.name'))
            ->success()
            ->greeting('Votre demande d\'accès a été approuvée !')
            ->line("Rôle attribué : **{$roleName}**")
            ->action('Se connecter', route('login'));

        if ($this->adminMessage) {
            $mail->line("Message de l'administrateur : {$this->adminMessage}");
        }

        return $mail->salutation('— '.config('app.name'));
    }
}
