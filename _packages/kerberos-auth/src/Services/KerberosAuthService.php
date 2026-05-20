<?php

namespace MokoGithub\KerberosAuth\Services;

use App\Models\User;
use MokoGithub\KerberosAuth\DTOs\AuthResult;
use MokoGithub\KerberosAuth\Models\AccessRequest;
use MokoGithub\KerberosAuth\Models\KerberosAttempt;
use MokoGithub\KerberosAuth\Notifications\NewAccessRequestNotification;
use MokoGithub\KerberosAuth\Notifications\UnknownKerberosAttemptNotification;

class KerberosAuthService
{
    public function getKerberosIdentifier(): ?string
    {
        if (config('kerberos.simulation_mode') && session()->has('simulated_kerberos')) {
            return session('simulated_kerberos');
        }

        $serverVar = config('kerberos.server_variable', 'REMOTE_USER');

        return $_SERVER[$serverVar] ?? null;
    }

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

        if (! $user) {
            $this->logAttempt($kerberos, 'unknown_user');
            $this->notifyAdminsUnknownUser($kerberos);

            return AuthResult::unknownUser($kerberos);
        }

        if (is_null($user->role_id)) {
            $this->logAttempt($kerberos, 'no_role', $user);

            return AuthResult::noRole($user, $kerberos);
        }

        $this->logAttempt($kerberos, 'success', $user);

        return AuthResult::success($user, $kerberos);
    }

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

    public function notifyAdminsNewRequest(AccessRequest $accessRequest): void
    {
        if (config('kerberos.admin_notification_mode', 'immediate') === 'disabled') {
            return;
        }

        foreach ($this->getAdminUsers() as $admin) {
            $admin->notify(new NewAccessRequestNotification(accessRequest: $accessRequest));
        }
    }

    protected function getAdminUsers(): \Illuminate\Support\Collection
    {
        return User::whereHas('role', function ($query) {
            $query->where('name', 'Admin');
        })->get();
    }

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

    public function disableSimulation(): void
    {
        session()->forget('simulated_kerberos');
    }

    public function isSimulationActive(): bool
    {
        return config('kerberos.simulation_mode') && session()->has('simulated_kerberos');
    }

    public function getSimulatedKerberos(): ?string
    {
        return $this->isSimulationActive() ? session('simulated_kerberos') : null;
    }
}
