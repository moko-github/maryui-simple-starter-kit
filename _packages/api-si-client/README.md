# moko/api-si-client

Package Laravel **privé** — client typé pour l'API SI, avec DTOs, events, jobs de synchronisation et réception des webhooks.

Ce package s'appuie sur [`moko/laravel-satellite`](https://github.com/moko-github/api-si-satellite) pour l'infrastructure HTTP générique.

---

## Prérequis

- PHP 8.2+
- Laravel 11 ou 12
- `moko/laravel-satellite` ^1.0
- `moko/maryui-simple-starter-kit` avec `php artisan app:install` (colonne `users.kerberos`, table `roles`)

---

## Installation

### 1. Référencer le repo privé dans `composer.json` de l'application

**Option A — dépôt local (développement) :**
```json
{
    "repositories": [
        { "type": "path", "url": "../api-si-client" }
    ]
}
```

**Option B — dépôt Git privé (production) :**
```json
{
    "repositories": [
        { "type": "vcs", "url": "git@github.com:moko-github/api-si-client.git" }
    ]
}
```

### 2. Installer les packages

```bash
composer require moko/laravel-satellite moko/api-si-client
```

### 3. Lancer l'installation

```bash
# Infrastructure générique (config satellite, canal de log)
php artisan satellite:install

# Config et migration SI
php artisan vendor:publish --tag=api-si-config
php artisan vendor:publish --tag=api-si-migrations
php artisan migrate
```

### 4. Variables d'environnement (`.env`)

```dotenv
API_SI_URL=https://api-si.example.com
API_SI_TOKEN=ton-token-sanctum
API_SI_TIMEOUT=10
API_SI_WEBHOOK_SECRET=un-secret-partage-long
```

### 5. Configurer `bootstrap/app.php`

```php
use Moko\ApiSi\Http\Middleware\SyncUserOnAuth;
use Moko\Satellite\Http\Middleware\VerifyWebhookSignature;

->withMiddleware(function (Middleware $middleware): void {
    $middleware->appendToGroup('web', SyncUserOnAuth::class);
    $middleware->validateCsrfTokens(except: ['webhooks/api-si']);
})
```

### 6. Ajouter la route webhook dans `routes/web.php`

```php
use Moko\ApiSi\Http\Controllers\WebhookController;
use Moko\Satellite\Http\Middleware\VerifyWebhookSignature;

Route::post('/webhooks/api-si', [WebhookController::class, 'handle'])
    ->middleware(VerifyWebhookSignature::class.':api-si.webhook_secret')
    ->name('webhooks.api-si');
```

### 7. Configurer le scheduler dans `routes/console.php`

```php
use Moko\ApiSi\Jobs\SyncSiUsersJob;
use Moko\ApiSi\Jobs\SyncAdminUsersJob;

Schedule::job(new SyncSiUsersJob)->everyFifteenMinutes()->withoutOverlapping();
Schedule::job(new SyncAdminUsersJob)->everyFifteenMinutes()->withoutOverlapping();
```

### 8. Enregistrer le webhook (post-déploiement)

```bash
php artisan webhooks:register
```

---

## Commandes Artisan disponibles

| Commande | Description |
|---|---|
| `php artisan sync:si-users` | Sync incrémental des utilisateurs SI |
| `php artisan sync:si-users --full` | Sync complet (ignore le curseur) |
| `php artisan admin:sync-users` | Sync des administrateurs uniquement |
| `php artisan webhooks:register` | Abonne ce satellite aux webhooks SI |

---

## Ajouter un nouveau DTO

Les DTOs sont des `readonly` classes PHP avec une factory `fromArray()`.

### Exemple : `SiServiceDTO`

**1. Créer `src/DTOs/SiServiceDTO.php` :**

```php
<?php

declare(strict_types=1);

namespace Moko\ApiSi\DTOs;

final readonly class SiServiceDTO
{
    public function __construct(
        public int $id,
        public string $name,
        public string $code,
        public bool $isActive,
    ) {}

    /** @param array<string, mixed> $data */
    public static function fromArray(array $data): self
    {
        return new self(
            id:       (int)    $data['id'],
            name:     (string) $data['name'],
            code:     (string) $data['code'],
            isActive: (bool)   ($data['is_active'] ?? true),
        );
    }
}
```

**2. Ajouter la méthode dans `ApiSiClient` :**

```php
// src/Services/ApiSiClient.php

public function listServices(): Collection
{
    $data = $this->get('/api/v1/services');

    return collect($data['data'] ?? [])
        ->map(fn (array $item) => SiServiceDTO::fromArray($item));
}
```

---

## Ajouter un nouvel event webhook

### Exemple : gérer `service.updated`

**1. Créer `src/Events/Si/ServiceUpdated.php` :**

```php
<?php

declare(strict_types=1);

namespace Moko\ApiSi\Events\Si;

final class ServiceUpdated
{
    /** @param array<string, mixed> $data */
    public function __construct(public readonly array $data) {}
}
```

**2. Ajouter le case dans `WebhookController::handle()` :**

```php
match ($event) {
    // ... cases existants ...
    'service.updated' => event(new ServiceUpdated($data)),
    default           => Log::channel('api-si')->warning("Webhook ignoré: {$event}"),
};
```

**3. Créer le listener dans l'application hôte (`app/Listeners/Si/HandleServiceUpdated.php`) :**

```php
<?php

namespace App\Listeners\Si;

use Moko\ApiSi\Events\Si\ServiceUpdated;

class HandleServiceUpdated
{
    public function handle(ServiceUpdated $event): void
    {
        // Traiter $event->data...
    }
}
```

**4. Enregistrer dans `AppServiceProvider::boot()` :**

```php
use Illuminate\Support\Facades\Event;
use Moko\ApiSi\Events\Si\ServiceUpdated;
use App\Listeners\Si\HandleServiceUpdated;

Event::listen(ServiceUpdated::class, HandleServiceUpdated::class);
```

**5. Mettre à jour les events abonnés :**

```bash
php artisan webhooks:register --events=user.disabled --events=user.enabled --events=user.updated --events=user.service_changed --events=service.updated
```

---

## Structure du package

```
src/
├── ApiSiServiceProvider.php
├── Console/Commands/
│   ├── RegisterApiSiWebhook.php
│   ├── SyncAdminUsers.php
│   └── SyncSiUsers.php
├── DTOs/
│   ├── HealthDTO.php
│   ├── SiEntityDTO.php
│   ├── SiRoleDTO.php
│   ├── SiUserDTO.php
│   ├── SyncStatusDTO.php
│   └── WebhookDTO.php
├── Events/Si/
│   ├── UserDisabled.php
│   ├── UserEnabled.php
│   ├── UserServiceChanged.php
│   └── UserUpdated.php
├── Http/
│   ├── Controllers/WebhookController.php
│   └── Middleware/SyncUserOnAuth.php
├── Jobs/
│   ├── SyncAdminUsersJob.php
│   └── SyncSiUsersJob.php
└── Services/
    ├── ApiSiClient.php
    └── ApiSiException.php
database/migrations/
└── 2026_04_28_000000_add_si_fields_to_users_table.php
config/
└── api-si.php
```
