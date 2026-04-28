<?php

declare(strict_types=1);

namespace App\Listeners\Si;

use App\Events\Si\UserDisabled;
use App\Models\User;
use Illuminate\Support\Facades\Cache;

/**
 * Réaction à user.disabled : on n'invalide que le cache de sync-on-auth.
 *
 * Tu peux ajouter ici la logique métier locale (basculer un flag is_active
 * selon ton schéma, révoquer des sessions, notifier un admin, etc.).
 */
final class DisableUserListener
{
    public function handle(UserDisabled $event): void
    {
        $kerberos = (string) ($event->data['kerberos'] ?? '');

        if ($kerberos === '') {
            return;
        }

        Cache::forget("user_synced:{$kerberos}");

        // à définir par le développeur du satellite :
        // User::where('kerberos', $kerberos)->update(['is_active' => false]);
    }
}
