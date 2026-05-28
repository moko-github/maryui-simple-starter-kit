<?php

namespace MokoGithub\KerberosAuth\Services;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use MokoGithub\KerberosAuth\Models\AccessRequest;
use MokoGithub\KerberosAuth\Models\Role;
use MokoGithub\KerberosAuth\Notifications\AccessRequestAcceptedNotification;
use MokoGithub\KerberosAuth\Notifications\AccessRequestRejectedNotification;

class AccessRequestService
{
    public function approve(
        AccessRequest $accessRequest,
        int $roleId,
        ?string $adminMessage,
        User $adminUser
    ): AccessRequest {
        return DB::transaction(function () use ($accessRequest, $roleId, $adminMessage, $adminUser) {
            $user = User::find($accessRequest->user_id);

            if (! $user) {
                $user = User::create([
                    'name' => $accessRequest->kerberos,
                    'email' => $accessRequest->kerberos,
                    'kerberos' => $accessRequest->kerberos,
                    'password' => bcrypt(str()->random(32)),
                    'role_id' => $roleId,
                ]);

                $accessRequest->update(['user_id' => $user->id]);
            } else {
                $user->update(['role_id' => $roleId]);
            }

            $accessRequest->update([
                'status' => 'approved',
                'processed_by' => $adminUser->id,
                'processed_at' => now(),
                'admin_message' => $adminMessage,
            ]);

            $user->notify(new AccessRequestAcceptedNotification($accessRequest, $adminMessage));

            return $accessRequest->fresh();
        });
    }

    public function reject(
        AccessRequest $accessRequest,
        string $adminMessage,
        User $adminUser
    ): AccessRequest {
        return DB::transaction(function () use ($accessRequest, $adminMessage, $adminUser) {
            $accessRequest->update([
                'status' => 'rejected',
                'processed_by' => $adminUser->id,
                'processed_at' => now(),
                'admin_message' => $adminMessage,
            ]);

            if ($accessRequest->user) {
                $accessRequest->user->notify(
                    new AccessRequestRejectedNotification($accessRequest, $adminMessage)
                );
            }

            return $accessRequest->fresh();
        });
    }

    public function getPendingCount(): int
    {
        return AccessRequest::where('status', 'pending')->count();
    }

    /** @return array<int, array<string, mixed>> */
    public function getAvailableRoles(): array
    {
        return Role::orderBy('name')->get()->toArray();
    }
}
