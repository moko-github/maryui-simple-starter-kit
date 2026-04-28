<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use App\Models\User;
use App\Services\ApiSiClient;
use App\Services\ApiSiException;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

/**
 * À la première requête Kerberos d'un utilisateur dans la journée, va
 * chercher le profil à jour auprès de l'API SI et upsert localement.
 *
 * Mise en cache 24 h pour éviter de requêter l'API à chaque requête.
 * En cas d'indisponibilité de l'API SI, on continue gracieusement si
 * l'utilisateur est déjà connu localement, sinon 503.
 */
final class SyncUserOnAuth
{
    public function __construct(private readonly ApiSiClient $api) {}

    public function handle(Request $request, Closure $next)
    {
        $kerberos = (string) $request->server('REMOTE_USER');

        if ($kerberos === '') {
            return $next($request);
        }

        $cacheKey = "user_synced:{$kerberos}";

        if (Cache::has($cacheKey)) {
            return $next($request);
        }

        try {
            $dto = $this->api->getUser($kerberos);
        } catch (ApiSiException $e) {
            Log::channel('api-si')->warning('[SyncUserOnAuth] API SI indisponible', [
                'kerberos' => $kerberos,
                'message'  => $e->getMessage(),
                'status'   => $e->statusCode,
            ]);

            if (! User::where('kerberos', $kerberos)->exists()) {
                abort(503, 'API SI indisponible et utilisateur inconnu localement.');
            }

            return $next($request);
        }

        User::updateOrCreate(
            ['kerberos' => $dto->kerberos],
            [
                'name'         => $dto->name,
                'email'        => $dto->email,
                'matricule'    => $dto->matricule,
                'rank'         => $dto->rank,
                'phone_number' => $dto->phoneNumber,
                'room_number'  => $dto->roomNumber,
                'entity_name'  => $dto->entityName,
                'si_synced_at' => now(),
            ]
        );

        Cache::put($cacheKey, true, now()->addHours(24));

        return $next($request);
    }
}
