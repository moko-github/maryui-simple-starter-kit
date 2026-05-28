# kerberos-auth

Package Laravel d'authentification SSO Kerberos via la variable serveur `REMOTE_USER`.

## Fonctionnalités

- Authentification automatique via `REMOTE_USER` (Apache/Nginx Kerberos)
- Gestion des demandes d'accès pour les comptes sans rôle
- Mode simulation pour les environnements de développement
- Composants Livewire 4 inclus (access-denied, request-access, simulate-kerberos, simulation-banner)
- Migrations, seeders et commandes artisan inclus

## Installation via git subtree

> **Attention requise** : Remplacez `YOUR_REPO_URL` dans les commandes par l'URL du dépôt avec l'ip par exemple.
> - `http://257.257.257.257/equipe/kerberos-auth.git`

```bash
# 1. Ajouter le remote
git remote add kerberos-auth YOUR_REPO_URL
git fetch kerberos-auth

# 2. Intégrer les fichiers comme subtree
git subtree add --prefix=packages/kerberos-auth kerberos-auth main --squash
```

```jsonc
// 3. composer.json — ajouter dans "autoload.psr-4"
"MokoGithub\\KerberosAuth\\": "packages/kerberos-auth/src/"
```

```bash
composer dump-autoload
```

```php
// 4. bootstrap/providers.php
MokoGithub\KerberosAuth\KerberosServiceProvider::class,
```

```bash
# 5. Lancer l'installateur
php artisan kerberos:install
```

## Mise à jour

```bash
git subtree pull --prefix=packages/kerberos-auth kerberos-auth main --squash
composer dump-autoload
php artisan migrate
```

## Configuration .env

```env
KERBEROS_ENABLED=false
KERBEROS_SERVER_VAR=REMOTE_USER
KERBEROS_FALLBACK_AUTH=true
KERBEROS_SIMULATION_MODE=false
KERBEROS_ADMIN_EMAILS=
KERBEROS_ADMIN_NOTIFICATION_MODE=immediate
KERBEROS_AUTO_CLEANUP_DAYS=30
KERBEROS_ALLOWED_DOMAINS=
```

## Commandes artisan

```bash
php artisan kerberos:install          # Installation initiale
php artisan kerberos:purge-attempts   # Purge les tentatives anciennes
```

## Scheduler

`kerberos:purge-attempts` est automatiquement planifié à 03h00 après `kerberos:install`.
