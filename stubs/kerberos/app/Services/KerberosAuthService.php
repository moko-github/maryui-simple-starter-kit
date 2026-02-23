<?php

namespace App\Services;

use App\DTOs\AuthResult;
use App\Models\AccessRequest;
use App\Models\KerberosAttempt;
use App\Models\User;
use App\Notifications\NewAccessRequestNotification;
use App\Notifications\UnknownKerberosAttemptNotification;

class KerberosAuthService
{
    /**
     * Get the Kerberos identifier from server variable or simulation session.
     */
    public function getKerberosIdentifier(): ?string
    {
        // Priority 1: Simulation mode (dev/staging only)
        if (config('kerberos.simulation_mode') && session()->has('simulated_kerberos')) {
            return session('simulated_kerberos');
        }

        // Priority 2: Real Kerberos from server variable
        $serverVar = config('kerberos.server_variable', 'REMOTE_USER');

        return $_SERVER[$serverVar] ?? null;
    }

    /**
     * Authenticate user via Kerberos and return AuthResult.
     *
     * Scenarios:
     * 1. SUCCESS      → user found with role → auto-login
     * 2. NO_ROLE      → user found without role → access request form
     * 3. UNKNOWN_USER → user not in database → access denied + admin notification
     * 4. NO_KERBEROS  → REMOTE_USER not present → fallback to login form
     */
    public function authenticate(): AuthResult
    {
        if (! config('kerberos.enabled') && ! $this->isSimulationActive()) {
            return AuthResult::noKerberos();
        }

        $kerberos = $this->getKerberosIdentifier();

        if (empty($kerberos)) {
            return AuthResult::noKerberos();
        }

        $user = User::where('kerberos', $kerberos)->first();

        // Scenario 3: Unknown user
        if (! $user) {
            $this->logAttempt($kerberos, 'unknown_user');
            $this->notifyAdminsUnknownUser($kerberos);

            return AuthResult::unknownUser($kerberos);
        }

        // Scenario 2: No role assigned
        if (is_null($user->role_id)) {
            $this->logAttempt($kerberos, 'no_role', $user);

            return AuthResult::noRole($user, $kerberos);
        }

        // Scenario 1: Success
        $this->logAttempt($kerberos, 'success', $user);

        return AuthResult::success($user, $kerberos);
    }

    /**
     * Create an access request for a user without a role.
     */
    public function createAccessRequest(User $user, string $kerberos, string $justification): AccessRequest
    {
        $accessRequest = AccessRequest::create([
            'user_id' => $user->id,
            'kerberos' => $kerberos,
            'justification' => $justification,
            'status' => 'pending',
        ]);

        $this->notifyAdminsNewRequest($accessRequest);

        return $accessRequest;
    }

    /**
     * Log a Kerberos authentication attempt.
     */
    public function logAttempt(string $kerberos, string $result, ?User $user = null): KerberosAttempt
    {
        return KerberosAttempt::create([
            'kerberos' => $kerberos,
            'result' => $result,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'attempted_at' => now(),
        ]);
    }

    /**
     * Notify all administrators of an unknown Kerberos attempt.
     */
    public function notifyAdminsUnknownUser(string $kerberos): void
    {
        if (config('kerberos.admin_notification_mode', 'immediate') === 'disabled') {
            return;
        }

        foreach ($this->getAdminUsers() as $admin) {
            $admin->notify(new UnknownKerberosAttemptNotification(
                kerberos: $kerberos,
                ipAddress: request()->ip() ?? '',
                userAgent: request()->userAgent() ?? '',
                attemptedAt: now()
            ));
        }
    }

    /**
     * Notify all administrators of a new access request.
     */
    public function notifyAdminsNewRequest(AccessRequest $accessRequest): void
    {
        if (config('kerberos.admin_notification_mode', 'immediate') === 'disabled') {
            return;
        }

        foreach ($this->getAdminUsers() as $admin) {
            $admin->notify(new NewAccessRequestNotification(accessRequest: $accessRequest));
        }
    }

    /**
     * Get all administrator users (role name = Admin).
     */
    protected function getAdminUsers(): \Illuminate\Support\Collection
    {
        return User::whereHas('role', function ($query) {
            $query->where('name', 'Admin');
        })->get();
    }

    /**
     * Enable Kerberos simulation mode (dev/staging only).
     */
    public function enableSimulation(string $kerberos): void
    {
        if (! config('kerberos.simulation_mode')) {
            throw new \RuntimeException('Kerberos simulation mode is not enabled in config.');
        }

        if (app()->environment('production')) {
            throw new \RuntimeException('Kerberos simulation is not allowed in production.');
        }

        session(['simulated_kerberos' => $kerberos]);
    }

    /**
     * Disable Kerberos simulation mode.
     */
    public function disableSimulation(): void
    {
        session()->forget('simulated_kerberos');
    }

    /**
     * Check if simulation mode is currently active.
     */
    public function isSimulationActive(): bool
    {
        return config('kerberos.simulation_mode') && session()->has('simulated_kerberos');
    }

    /**
     * Get the currently simulated Kerberos identifier.
     */
    public function getSimulatedKerberos(): ?string
    {
        return $this->isSimulationActive() ? session('simulated_kerberos') : null;
    }
}
