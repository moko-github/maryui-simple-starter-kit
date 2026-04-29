<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Services\ApiSiClient;
use App\Services\ApiSiException;
use Illuminate\Console\Command;

/**
 * Enregistre ce satellite auprès de l'API SI pour recevoir les webhooks.
 *
 * Idempotent côté API SI grâce à la table `webhook_subscriptions`. À lancer
 * une fois en post-déploiement (Envoy task ou hook CI).
 */
class RegisterApiSiWebhook extends Command
{
    protected $signature = 'webhooks:register
                            {--events=* : Liste des events (par défaut: user.disabled, user.enabled, user.updated, user.service_changed)}';

    protected $description = 'Enregistre ce satellite auprès de l\'API SI pour recevoir les webhooks user.*.';

    public function handle(ApiSiClient $api): int
    {
        $secret = (string) config('api-si.webhook_secret');

        if ($secret === '') {
            $this->error('API_SI_WEBHOOK_SECRET non défini dans .env. Annulation.');

            return self::FAILURE;
        }

        $events = $this->option('events');

        if ($events === [] || $events === null) {
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
