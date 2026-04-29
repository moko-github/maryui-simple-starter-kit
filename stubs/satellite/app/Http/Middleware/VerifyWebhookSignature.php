<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * Vérifie la signature HMAC SHA-256 d'un webhook entrant depuis l'API SI.
 *
 * Le secret partagé est dans config('api-si.webhook_secret').
 * Comparaison en temps constant via hash_equals (anti timing-attack).
 */
final class VerifyWebhookSignature
{
    public function handle(Request $request, Closure $next)
    {
        $signature = (string) $request->header('X-Webhook-Signature');
        $secret    = (string) config('api-si.webhook_secret');

        if ($secret === '') {
            abort(500, 'API SI webhook_secret not configured');
        }

        $expected = hash_hmac('sha256', $request->getContent(), $secret);

        if (! hash_equals($expected, $signature)) {
            abort(401, 'Invalid webhook signature');
        }

        return $next($request);
    }
}
