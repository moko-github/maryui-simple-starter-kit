# Plan — Commande `php artisan satellite:install` dans `maryui-simple-starter-kit`

## Context

Le repo `admin-console` expose un guide `docs/CREER-UN-SATELLITE.md` qui décrit comment transformer une application Laravel en « satellite client » de l'API SI : copier `ApiSiClient`, les DTOs, configurer le polling, le sync-on-auth Kerberos, et un récepteur de webhooks.

Aujourd'hui, ce guide doit être suivi à la main par chaque équipe satellite. On automatise tout dans une commande `php artisan satellite:install` embarquée dans `maryui-simple-starter-kit`, en s'alignant sur le pattern existant `php artisan app:install` (`app/Console/Commands/InstallCommand.php`) :

- **idempotente** (chaque étape vérifie l'état avant d'écrire) ;
- **interactive** (Laravel Prompts : `intro`/`confirm`/`note`/`outro`) ;
- **stubs embarqués** sous `stubs/satellite/` (le repo `admin-console` est privé, pas de fetch réseau) ;
- **prérequis Kerberos obligatoire** : si la colonne `users.kerberos` n'existe pas, on **abort** avec un message demandant de lancer `php artisan app:install` d'abord ;
- **périmètre v1 = sections 1 à 6 du doc** (cœur HTTP + DTOs + config + polling + sync-on-auth + webhooks).

Résultat attendu : `php artisan satellite:install` rend le projet courant capable de consommer l'API SI immédiatement (il reste à renseigner `.env` puis lancer `migrate` + `webhooks:register`).

---

## Architecture cible

### Nouvelle commande
- `app/Console/Commands/SatelliteInstallCommand.php` — signature `satellite:install`, structure miroir de `InstallCommand` : `handle()` → vérifs préalables → suite de méthodes protégées idempotentes.

### Stubs embarqués
Arborescence proposée sous `stubs/satellite/`, en miroir des chemins finaux (convention identique à `stubs/kerberos/`) :

```
stubs/satellite/
├── app/
│   ├── Console/Commands/
│   │   ├── SyncSiUsers.php              # signature: sync:si-users {--full}
│   │   ├── SyncAdminUsers.php           # signature: admin:sync-users {--since}  (copié d'admin-console)
│   │   └── RegisterApiSiWebhook.php     # signature: webhooks:register
│   ├── DTOs/
│   │   ├── SiUserDTO.php
│   │   ├── SiRoleDTO.php
│   │   ├── SiEntityDTO.php
│   │   ├── HealthDTO.php
│   │   ├── SyncStatusDTO.php
│   │   └── WebhookDTO.php
│   ├── Events/Si/
│   │   ├── UserDisabled.php
│   │   ├── UserEnabled.php
│   │   ├── UserUpdated.php
│   │   └── UserServiceChanged.php
│   ├── Http/
│   │   ├── Controllers/WebhookController.php
│   │   └── Middleware/
│   │       ├── SyncUserOnAuth.php
│   │       └── VerifyWebhookSignature.php
│   ├── Jobs/
│   │   ├── SyncSiUsersJob.php           # nouveau, assigne le rôle "User" par défaut
│   │   └── SyncAdminUsersJob.php        # copié d'admin-console (filtre isAdministrator → "Admin")
│   ├── Listeners/Si/
│   │   ├── DisableUserListener.php
│   │   ├── EnableUserListener.php
│   │   └── InvalidateUserCacheListener.php
│   └── Services/
│       ├── ApiSiClient.php              # version allégée (sans méthodes admin-only)
│       └── ApiSiException.php
├── config/
│   └── api-si.php                       # + clé webhook_secret
└── database/migrations/
    └── 2026_04_28_000000_add_si_fields_to_users_table.php
```

### Étapes de `handle()` (ordre d'exécution)

1. `intro("Installation du satellite API SI")` + `confirm()` initial.
2. **`assertKerberosInstalled()`** — `Schema::hasColumn('users', 'kerberos')` ; sinon `error()` + `outro()` qui dit « Lance `php artisan app:install` puis ré-exécute `satellite:install` » et `return self::FAILURE`.
3. **`publishStubs()`** — copie récursive `stubs/satellite/**` vers la racine projet ; pour chaque fichier, skip si la cible existe déjà sauf `--force`.
4. **`appendEnvVariables()`** — ajoute à `.env` ET `.env.example` (idempotent via `str_contains`) :
   ```
   API_SI_URL=https://api-si.justice.gouv.fr
   API_SI_TOKEN=
   API_SI_TIMEOUT=10
   API_SI_SCRAMBLE_URL=https://api-si.justice.gouv.fr/docs/api
   LOG_API_SI_LEVEL=debug
   API_SI_WEBHOOK_SECRET={générer via Str::random(64)}
   ```
5. **`configureLoggingChannel()`** — patch `config/logging.php` : ajoute le canal `api-si` dans le tableau `channels` (insertion avant la fermeture `]`, idempotent via marqueur `'api-si' =>`).
6. **`configureAppServiceProvider()`** — patch `app/Providers/AppServiceProvider.php::register()` : ajoute `$this->app->singleton(\App\Services\ApiSiClient::class);` (idempotent via `str_contains('singleton(ApiSiClient')`).
7. **`configureBootstrap()`** — patch `bootstrap/app.php` : `appendToGroup('web', \App\Http\Middleware\SyncUserOnAuth::class)` (idempotent via `str_contains('SyncUserOnAuth')`).
8. **`configureWebhookRoute()`** — `File::append()` sur `routes/api.php` :
   ```php
   Route::post('/webhooks/api-si', [\App\Http\Controllers\WebhookController::class, 'handle'])
       ->middleware(\App\Http\Middleware\VerifyWebhookSignature::class)
       ->name('webhooks.api-si');
   ```
   (idempotent via `str_contains('webhooks.api-si')`).
9. **`configureScheduler()`** — `File::append()` sur `routes/console.php` :
   ```php
   \Illuminate\Support\Facades\Schedule::job(new \App\Jobs\SyncSiUsersJob)->everyFifteenMinutes()->withoutOverlapping();
   \Illuminate\Support\Facades\Schedule::job(new \App\Jobs\SyncAdminUsersJob)->everyFifteenMinutes()->withoutOverlapping();
   ```
   (idempotent via `str_contains('SyncSiUsersJob')`).
10. **`runMigration()`** — `$this->call('migrate', ['--force' => true])` pour appliquer la nouvelle migration `add_si_fields_to_users_table`.
11. **`outro()`** — checklist finale : remplir `API_SI_TOKEN`, `API_SI_WEBHOOK_SECRET` ; lancer `php artisan webhooks:register` après déploiement ; abilities Sanctum à demander : `users:read users:sync entities:read entities:sync webhooks:manage`.

---

## Détails par fichier publié

### `ApiSiClient.php` (allégé)
Version dérivée de `admin-console/app/Services/ApiSiClient.php`, on **garde** : `health()`, `getUser()`, `listUsers()`, `syncStatus()`, helpers privés `get/post/put/delete`. On **supprime** : `listClients`, `getClient`, `createClient`, `updateClient`, `createToken`, `deleteToken`, `listWebhooks`, `listWebhookDeliveries`, `retryWebhookDelivery`, `listAuditLogs`. On **ajoute** : `subscribeWebhook(string $url, array $events, string $secret): WebhookDTO`. Imports nettoyés (`ApiClientDTO`, `AuditLogDTO`, `WebhookDeliveryDTO` retirés).

### `SyncSiUsersJob.php` (nouveau, à écrire)
Adapté de `SyncAdminUsersJob` :
- Cache keys distinctes (`si_users_sync_last_id`, `si_users_sync_last_run_at`).
- **Pas** de filtre `isAdministrator()` : on parcourt tous les users.
- **Stratégie de rôle** (selon ta préférence) : si `$dto->isAdministrator()` → `Role::firstOrCreate(['name' => 'Admin'])`, sinon `Role::firstOrCreate(['name' => 'User'])`. Cela évite le doublon de traitement avec `SyncAdminUsersJob` et permet au dev d'affiner ensuite.
- `upsertUser()` réduit aux colonnes connues du starter kit : `name`, `email`, `kerberos`, `matricule`, `rank`, `phone_number`, `room_number`, `entity_name`, `role_id`, `si_synced_at`, `password` (mot de passe aléatoire si nouveau).

### `SyncAdminUsersJob.php` + `SyncAdminUsers.php`
Copiés tels quels depuis `admin-console`, namespace `App\Jobs\` et `App\Console\Commands\`. Conserve la signature `admin:sync-users` et le filtre `isAdministrator()`.

### `SyncUserOnAuth.php`
Middleware identique au pseudo-code §5 du doc : lit `$request->server('REMOTE_USER')`, cache 24 h via `Cache::has("user_synced:{$kerberos}")`, fallback gracieux si `ApiSiException` ET user déjà connu localement, `abort(503)` sinon.

### `VerifyWebhookSignature.php` + `WebhookController.php`
Middleware HMAC SHA-256 + `hash_equals` (constant-time), controller qui dispatch `match` vers les events `App\Events\Si\*`. Events = simples DTO porteurs d'`array $data`.

### `RegisterApiSiWebhook.php`
Commande `webhooks:register` qui appelle `ApiSiClient::subscribeWebhook(route('webhooks.api-si'), [...], config('api-si.webhook_secret'))`.

### Migration `add_si_fields_to_users_table`
Datée `2026_04_28_000000_*` (postérieure aux migrations `app:install`), ajoute `matricule`, `rank`, `phone_number`, `room_number`, `entity_name`, `si_synced_at`, toutes nullable, après `kerberos`.

---

## Idempotence et garde-fous

- Chaque méthode `configure*` commence par un `str_contains()` sur un marqueur unique du contenu déjà inséré → `return` si présent.
- `publishStubs()` : par défaut, **skip** les cibles existantes. Flag `--force` pour écraser. Affiche `note()` listant les fichiers publiés / skippés.
- Détection du prérequis Kerberos via `Schema::hasColumn('users', 'kerberos')` (et non via la présence de la migration, qui peut avoir été modifiée).
- Pas de création automatique des rôles `User`/`Admin` côté commande : ils sont créés à la volée par les jobs (`Role::firstOrCreate`), évite de doublonner avec `RolesSeeder` d'`app:install`.

---

## Fichiers critiques à modifier

| Fichier | Action |
|---|---|
| `app/Console/Commands/SatelliteInstallCommand.php` | **Créer** (commande principale) |
| `stubs/satellite/**` | **Créer** (toute l'arborescence ci-dessus) |
| `app/Providers/AppServiceProvider.php` | **Patché** par la commande (singleton ApiSiClient) |
| `bootstrap/app.php` | **Patché** par la commande (middleware web) |
| `routes/console.php` | **Patché** par la commande (scheduler) |
| `routes/api.php` | **Patché** par la commande (route webhook) |
| `config/logging.php` | **Patché** par la commande (canal api-si) |
| `.env` / `.env.example` | **Patché** par la commande |

### Existants à réutiliser (pas de duplication)
- Pattern `InstallCommand` (`app/Console/Commands/InstallCommand.php`) : copier le style des helpers privés (`replaceInFile`, `appendEnvVariables`, structure `handle()`).
- Migration Kerberos `2025_11_18_100001_add_kerberos_columns_to_users_table.php` (apportée par `app:install`) : fournit `users.kerberos` et `users.role_id`, prérequis dur.
- Modèle `App\Models\Role` (apporté par `app:install`) : utilisé par les jobs de sync.

---

## Tests (Pest)

Sous `tests/Feature/Console/SatelliteInstallTest.php` :

1. **Abort si Kerberos absent** — drop `users.kerberos` puis `artisan('satellite:install')` → assertExitCode `FAILURE`, assertOutputContains `app:install`.
2. **Run nominal** — base avec colonne `kerberos` → `artisan('satellite:install')` → vérifie présence de chaque fichier publié (`assertFileExists` sur `app/Services/ApiSiClient.php`, etc.).
3. **Idempotence** — exécuter 2× la commande, vérifier que `.env` ne contient qu'**une** occurrence de `API_SI_URL`, idem pour les patches `bootstrap/app.php` et `routes/console.php`.
4. **Migration appliquée** — `Schema::hasColumn('users', 'si_synced_at')` après run.
5. **Flag `--force`** — modifier un stub publié, relancer avec `--force`, vérifier que le fichier a été réécrit.

Pour `ApiSiClient` (test unitaire séparé, après publication des stubs) : `Http::fake()` + assertion sur les endpoints appelés (`/api/v1/health`, `/api/v1/users/{kerberos}`, `/api/v1/users?cursor=...`).

---

## Vérification end-to-end

Sur un projet vierge issu du starter kit :

```bash
# 1. Pré-requis : Kerberos
php artisan app:install
# (répondre "yes" à l'activation Kerberos)

# 2. Satellite
php artisan satellite:install

# 3. Renseigner les valeurs dans .env :
#    API_SI_URL=...   API_SI_TOKEN=...

# 4. Lancer un sync de test
php artisan sync:si-users --full
php artisan admin:sync-users

# 5. Vérifier en BDD
php artisan tinker
>>> User::whereNotNull('si_synced_at')->count();

# 6. Re-lancer la commande pour vérifier l'idempotence
php artisan satellite:install
# → "Aucune modification" sur toutes les sections déjà installées

# 7. Webhook (si l'API SI cible est joignable)
php artisan webhooks:register
```

Tests automatisés :
```bash
./vendor/bin/pest tests/Feature/Console/SatelliteInstallTest.php
./vendor/bin/pest               # full suite, vérifie absence de régression
./vendor/bin/pint --test         # style
```

---

## Branche & livraison

- Branche : `claude/satellite-documentation-FnaP1` (déjà fixée par l'environnement).
- Une seule PR couvrant les sections 1 à 6.
- Plan approuvé — implémentation en cours sur Sonnet (claude-sonnet-4-6).
