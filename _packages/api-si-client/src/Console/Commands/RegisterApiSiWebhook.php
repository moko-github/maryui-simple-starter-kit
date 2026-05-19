<?php

declare(strict_types=1);

namespace Moko\ApiSi\Console\Commands;

use Illuminate\Console\Command;
use Moko\ApiSi\Services\ApiSiClient;
use Moko\ApiSi\Services\ApiSiException;

/** Enregistre ce satellite auprès de l'API SI pour recevoir les webhooks. */
class RegisterApiSiWebhook extends Command
{
    protected $signature = 'webhooks:register
                            {--events=* : Liste des events (défaut: user.disabled, user.enabled, user.updated, user.service_changed)}';

    protected $description = "Enregistre ce satellite auprès de l'API SI pour recevoir les webhooks.";

    public function handle(ApiSiClient $api): int
    {
        $secret = (string) config('api-si.webhook_secret');

        if ($secret === '') {
            $this->error('API_SI_WEBHOOK_SECRET non défini dans .env.');

            return self::FAILURE;
        }

        $events = (array) $this->option('events');

        if ($events === []) {
            $events = ['user.disabled', 'user.enabled', 'user.updated', 'user.service_changed'];
        }

        $url = route('webhooks.api-si');

        try {
            $webhook = $api->subscribeWebhook(url: $url, events: $events, secret: $secret);
        } catch (ApiSiException $e) {
            $this->error("Échec de l'abonnement webhook : {$e->getMessage()} (HTTP {$e->statusCode})");

            return self::FAILURE;
        }

        $this->info("Webhook enregistré (id={$webhook->id}) sur {$url}");
        $this->line('Events : '.implode(', ', $webhook->events));

        return self::SUCCESS;
    }
}
