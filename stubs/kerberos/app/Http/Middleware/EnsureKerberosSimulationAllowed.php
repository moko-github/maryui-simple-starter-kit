<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureKerberosSimulationAllowed
{
    public function handle(Request $request, Closure $next): Response
    {
        if (config('kerberos.simulation_mode') && app()->environment('production')) {
            throw new \RuntimeException(
                'Kerberos simulation mode is not allowed in production. '.
                'Set KERBEROS_SIMULATION_MODE=false in your .env file.'
            );
        }

        return $next($request);
    }
}
