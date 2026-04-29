<?php

declare(strict_types=1);

namespace App\Jobs;

use App\DTOs\SiUserDTO;
use App\Models\Role;
use App\Models\User;
use App\Services\ApiSiClient;
use App\Services\ApiSiException;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

/**
 * Synchronise tous les utilisateurs actifs depuis l'API SI vers la table
 * locale `users` (upsert sur la colonne `kerberos`).
 *
 * Stratégie cursor-based avec curseur mémorisé dans le cache. Les utilisateurs
 * remontés sont assignés au rôle "Admin" si le DTO indique isAdministrator(),
 * sinon au rôle "User" par défaut.
 */
class SyncSiUsersJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    private const CACHE_KEY_LAST_ID = 'si_users_sync_last_id';

    private const CACHE_KEY_LAST_RUN = 'si_users_sync_last_run_at';

    public function __construct(public readonly bool $fullSync = false) {}

    public function handle(ApiSiClient $client): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'Admin']);
        $userRole  = Role::firstOrCreate(['name' => 'User']);

        $lastId = $this->fullSync ? 0 : (int) Cache::get(self::CACHE_KEY_LAST_ID, 0);

        $maxId    = $lastId;
        $upserted = 0;

        try {
            $cursor = $lastId > 0 ? (string) $lastId : null;

            do {
                ['users' => $users, 'nextCursor' => $nextCursor] = $client->listUsers(
                    cursor: $cursor,
                    withRoles: true,
                    withEntity: true,
                );

                foreach ($users as $dto) {
                    /** @var SiUserDTO $dto */
                    $roleId = $dto->isAdministrator() ? $adminRole->id : $userRole->id;
                    $this->upsertUser($dto, $roleId);
                    $upserted++;

                    if ($dto->id > $maxId) {
                        $maxId = $dto->id;
                    }
                }

                $cursor = $nextCursor;

            } while ($cursor !== null);

        } catch (ApiSiException $e) {
            Log::channel('api-si')->error('[SyncSiUsersJob] API SI error', [
                'message'  => $e->getMessage(),
                'endpoint' => $e->endpoint,
                'status'   => $e->statusCode,
            ]);

            return;
        }

        if ($maxId > $lastId) {
            Cache::forever(self::CACHE_KEY_LAST_ID, $maxId);
        }

        Cache::forever(self::CACHE_KEY_LAST_RUN, now()->toIso8601String());

        Log::channel('api-si')->info("[SyncSiUsersJob] Done. Upserted={$upserted}, lastId={$maxId}, fullSync={$this->fullSync}");
    }

    private function upsertUser(SiUserDTO $dto, int $roleId): void
    {
        User::updateOrCreate(
            ['kerberos' => $dto->kerberos],
            [
                'name'          => $dto->name,
                'email'         => $dto->email,
                'matricule'     => $dto->matricule,
                'rank'          => $dto->rank,
                'phone_number'  => $dto->phoneNumber,
                'room_number'   => $dto->roomNumber,
                'entity_name'   => $dto->entityName,
                'role_id'       => $roleId,
                'si_synced_at'  => now(),
                'password'      => $this->resolvePassword($dto->kerberos),
            ]
        );
    }

    /**
     * Conserve le password existant si l'utilisateur existe déjà,
     * sinon génère un token aléatoire non utilisable (auth Kerberos uniquement).
     */
    private function resolvePassword(string $kerberos): string
    {
        $existing = User::where('kerberos', $kerberos)->value('password');

        return $existing ?? bcrypt(Str::random(64));
    }
}
