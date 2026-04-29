<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Events\Si\UserDisabled;
use App\Events\Si\UserEnabled;
use App\Events\Si\UserServiceChanged;
use App\Events\Si\UserUpdated;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

/**
 * Récepteur central des webhooks de l'API SI.
 *
 * Map les events API SI vers des events Laravel internes ; les listeners
 * métier consomment ces derniers (cf. App\Listeners\Si\*).
 */
final class WebhookController
{
    public function handle(Request $request): JsonResponse
    {
        $event = (string) $request->input('event');
        $data  = (array) $request->input('data');

        match ($event) {
            'user.disabled'        => event(new UserDisabled($data)),
            'user.enabled'         => event(new UserEnabled($data)),
            'user.updated'         => event(new UserUpdated($data)),
            'user.service_changed' => event(new UserServiceChanged($data)),
            default                => Log::channel('api-si')->warning("Webhook ignoré: {$event}"),
        };

        return response()->json(['status' => 'ok']);
    }
}
