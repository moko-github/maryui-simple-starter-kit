<?php

namespace App\Services;

use App\Models\AccessRequest;
use App\Models\Role;
use App\Models\User;
use App\Notifications\AccessRequestAcceptedNotification;
use App\Notifications\AccessRequestRejectedNotification;
use Illuminate\Support\Facades\DB;

class AccessRequestService
{
    /**
     * Approve an access request and assign the chosen role to the user.
     */
    public function approve(
        AccessRequest $accessRequest,
        int $roleId,
        ?string $adminMessage,
        User $adminUser
    ): AccessRequest {
        return DB::transaction(function () use ($accessRequest, $roleId, $adminMessage, $adminUser) {
            $user = User::find($accessRequest->user_id);

            if (! $user) {
                // Edge case: create the user record if it doesn't exist yet
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

    /**
     * Reject an access request.
     */
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

    /**
     * Count pending access requests (for badge display).
     */
    public function getPendingCount(): int
    {
        return AccessRequest::where('status', 'pending')->count();
    }

    /**
     * Get available roles for assignment in the approval form.
     *
     * @return array<int, array<string, mixed>>
     */
    public function getAvailableRoles(): array
    {
        return Role::orderBy('name')->get()->toArray();
    }
}
