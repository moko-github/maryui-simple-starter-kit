<?php

namespace MokoGithub\KerberosAuth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use MokoGithub\KerberosAuth\DTOs\AuthResult;
use MokoGithub\KerberosAuth\Services\KerberosAuthService;
use Symfony\Component\HttpFoundation\Response;

class KerberosAuthentication
{
    public function __construct(protected KerberosAuthService $kerberosService) {}

    public function handle(Request $request, Closure $next): Response
    {
        $excludedRoutes = [
            'access-denied',
            'access-request.create',
            'access-request.store',
            'logout',
            'livewire.*',
        ];

        if ($request->routeIs($excludedRoutes)) {
            return $next($request);
        }

        if (Auth::check()) {
            return $next($request);
        }

        $simulationActive = $this->kerberosService->isSimulationActive();

        if (! config('kerberos.enabled') && ! $simulationActive) {
            return $next($request);
        }

        $result = $this->kerberosService->authenticate();

        return match ($result->status) {
            AuthResult::SUCCESS => $this->handleSuccess($result, $request, $next),
            AuthResult::NO_ROLE => $this->handleNoRole($result),
            AuthResult::UNKNOWN_USER => $this->handleUnknownUser($result),
            default => $next($request),
        };
    }

    protected function handleSuccess(AuthResult $result, Request $request, Closure $next): Response
    {
        Auth::login($result->user, remember: true);

        return redirect()->intended(route('dashboard'));
    }

    protected function handleNoRole(AuthResult $result): Response
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        session([
            'pending_kerberos' => $result->kerberos,
            'pending_user_id' => $result->user->id,
        ]);

        return redirect()->route('access-request.create');
    }

    protected function handleUnknownUser(AuthResult $result): Response
    {
        Auth::logout();
        session()->invalidate();
        session()->regenerateToken();

        session(['unknown_kerberos' => $result->kerberos]);

        return redirect()->route('access-denied');
    }
}
