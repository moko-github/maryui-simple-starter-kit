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
            ->subject('⚠️ Tentative de connexion Kerberos inconnue - '.config('app.name'))
            ->error()
            ->greeting('Identifiant Kerberos non reconnu détecté')
            ->line("Une tentative d'authentification a été effectuée avec un identifiant Kerberos inconnu.")
            ->line("**Identifiant :** `{$this->kerberos}`")
            ->line("**Adresse IP :** {$this->ipAddress}")
            ->line("**Navigateur :** {$this->userAgent}")
            ->line("**Date/Heure :** {$this->attemptedAt->format('d/m/Y H:i:s')}")
            ->line("S'il s'agit d'un utilisateur légitime, ajoutez son identifiant Kerberos dans le système.")
            ->salutation('— '.config('app.name'));
    }
}
