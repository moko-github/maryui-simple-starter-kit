<?php

declare(strict_types=1);

namespace Moko\ApiSi\Http\Controllers;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Moko\ApiSi\Events\Si\UserDisabled;
use Moko\ApiSi\Events\Si\UserEnabled;
use Moko\ApiSi\Events\Si\UserServiceChanged;
use Moko\ApiSi\Events\Si\UserUpdated;

/**
 * Récepteur central des webhooks de l'API SI.
 * Mappe les events API SI vers des events Laravel internes.
 */
final class WebhookController
{
    public function handle(Request $request): JsonResponse
    {
        $event = (string) $request->input('event');
        $data  = (array)  $request->input('data');

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
