# `php artisan satellite:install`

> Commande d'installation interactive qui transforme un projet issu de `maryui-simple-starter-kit` en **satellite client de l'API SI**.
>
> Elle automatise les sections 1 à 6 du guide [`CREER-UN-SATELLITE.md`](../../admin-console/docs/CREER-UN-SATELLITE.md) (repo `admin-console`).

---

## Prérequis

**`php artisan app:install` doit avoir été lancé en premier**, avec l'option Kerberos activée.  
La commande vérifie la présence de la colonne `users.kerberos` — si elle est absente, elle s'arrête avec un message d'erreur explicite.

---

## Utilisation

```bash
# Installation interactive (recommandée)
php artisan satellite:install

# Écraser les stubs déjà publiés (ex. après une mise à jour du starter kit)
php artisan satellite:install --force
```

---

## Ce que fait la commande

| Étape | Action |
|---|---|
| 1 | Vérifie que `users.kerberos` existe (prérequis Kerberos) |
| 2 | Publie `stubs/satellite/**` vers les dossiers finaux (`app/`, `config/`, `database/`) |
| 3 | Patch `app/Models/User.php` : ajoute les colonnes SI à `$fillable` + cast `si_synced_at` |
| 4 | Patch `app/Providers/AppServiceProvider.php` : binding singleton `ApiSiClient` |
| 5 | Patch `bootstrap/app.php` : middleware `SyncUserOnAuth` + exclusion CSRF webhook |
| 6 | Patch `routes/web.php` : route `POST /webhooks/api-si` |
| 7 | Patch `routes/console.php` : scheduler `SyncSiUsersJob` + `SyncAdminUsersJob` (15 min) |
| 8 | Patch `config/logging.php` : canal `api-si` (fichier dédié `logs/api-si.log`) |
| 9 | Patch `.env` + `.env.example` : variables `API_SI_*` (secret webhook généré aléatoirement) |
| 10 | Lance `php artisan migrate` |

Chaque étape est **idempotente** : relancer la commande ne duplique rien.

---

## Fichiers publiés

```
app/
├── Console/Commands/
│   ├── SyncSiUsers.php          # php artisan sync:si-users {--full}
│   ├── SyncAdminUsers.php       # php artisan admin:sync-users {--since}
│   └── RegisterApiSiWebhook.php # php artisan webhooks:register
├── DTOs/
│   ├── SiUserDTO.php
│   ├── SiRoleDTO.php
│   ├── SiEntityDTO.php
│   ├── HealthDTO.php
│   ├── SyncStatusDTO.php
│   └── WebhookDTO.php
├── Events/Si/
│   ├── UserDisabled.php
│   ├── UserEnabled.php
│   ├── UserUpdated.php
│   └── UserServiceChanged.php
├── Http/
│   ├── Controllers/WebhookController.php
│   └── Middleware/
│       ├── SyncUserOnAuth.php
│       └── VerifyWebhookSignature.php
├── Jobs/
│   ├── SyncSiUsersJob.php       # sync tous les users (Admin/User selon isAdministrator())
│   └── SyncAdminUsersJob.php    # sync uniquement les administrateurs
├── Listeners/Si/
│   ├── DisableUserListener.php
│   ├── EnableUserListener.php
│   └── InvalidateUserCacheListener.php
└── Services/
    ├── ApiSiClient.php          # client HTTP allégé (sans méthodes admin-only)
    └── ApiSiException.php
config/
└── api-si.php
database/migrations/
└── 2026_04_28_000000_add_si_fields_to_users_table.php
```

---

## Après l'installation

### 1. Renseigner `.env`

```env
API_SI_URL=https://api-si.justice.gouv.fr
API_SI_TOKEN=<token fourni par l'équipe API SI>
API_SI_WEBHOOK_SECRET=<secret généré automatiquement — ne pas changer>
```

Abilities Sanctum à demander à l'équipe API SI :
```
users:read  users:sync  entities:read  entities:sync  webhooks:manage
```

### 2. Lancer un premier sync

```bash
php artisan sync:si-users --full
php artisan admin:sync-users
```

### 3. Enregistrer le récepteur webhook (post-déploiement)

```bash
php artisan webhooks:register
```

À relancer à chaque changement d'URL ou de secret.

---

## Stratégie de rôles

| Source | Rôle assigné |
|---|---|
| `SyncSiUsersJob` — utilisateur avec `isAdministrator()` | `Admin` |
| `SyncSiUsersJob` — autres utilisateurs | `User` |
| `SyncAdminUsersJob` | `Admin` uniquement |

Les rôles sont créés à la volée via `Role::firstOrCreate` — pas de seeder nécessaire.  
Adapte les listeners dans `app/Listeners/Si/` selon la logique métier de ton satellite.

---

## Idempotence et `--force`

- Sans `--force` : les fichiers déjà présents dans `app/`, `config/`, `database/` **ne sont pas écrasés**.
- Avec `--force` : tous les stubs sont réécrits (utile lors d'une mise à jour du starter kit).
- Les patches sur `bootstrap/app.php`, `routes/`, `config/logging.php` et `.env` sont toujours idempotents, indépendamment de `--force`.

---

## Tests

```bash
./vendor/bin/pest tests/Feature/SatelliteInstallCommandTest.php
./vendor/bin/pest   # suite complète
```
