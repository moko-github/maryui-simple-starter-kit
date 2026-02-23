# MaryUI Simple Starter Kit

Un starter kit Laravel léger basé sur [Mary UI](https://mary-ui.com), inspiré de [mary-ui-starter-kit](https://github.com/lauroguedes/mary-ui-starter-kit), simplifié pour une intégration sans `laravel-permission` et avec un module optionnel d'authentification Kerberos (SSO).

## Stack technique

| Composant | Version |
|-----------|---------|
| PHP | ^8.2 |
| Laravel | v12 |
| Livewire | v4 |
| Mary UI | v2 |
| Tailwind CSS | v4 |
| DaisyUI | v5 |
| Laravel Fortify | v1 |
| Pest | v3 |

## Fonctionnalités incluses

- **Authentification complète** via Fortify : connexion, inscription, mot de passe oublié, réinitialisation, vérification e-mail
- **Double authentification (2FA)** : activation, désactivation, codes de récupération
- **Page de paramètres** : profil, mot de passe, apparence, 2FA
- **Layout avec sidebar** responsive (Mary UI) avec navigation mobile
- **Module Kerberos optionnel** : SSO via `REMOTE_USER` (Apache/Nginx)
- **Tests Pest** préconfigurés
- **Formatage** avec Laravel Pint

## Démarrage rapide

```bash
composer create-project moko-github/maryui-simple-starter-kit mon-app
cd mon-app
npm install && npm run build
```

L'installateur interactif vous proposera d'activer le module Kerberos pendant la création du projet.

## Installation manuelle

```bash
git clone https://github.com/moko-github/maryui-simple-starter-kit.git mon-app
cd mon-app
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install && npm run build
```

## Développement

```bash
# Démarre le serveur, la queue et Vite en parallèle
composer run dev

# Lancer les tests
php artisan test

# Formater le code
vendor/bin/pint
```

## Module optionnel : Authentification Kerberos (SSO)

Ce starter kit intègre un module d'authentification unique (SSO) via Kerberos, activable à l'installation ou à tout moment.

### Activation

```bash
php artisan app:install
```

L'installateur copie les fichiers nécessaires, configure le middleware et les routes, ajoute les variables d'environnement et exécute les migrations.

### Configuration `.env`

```env
KERBEROS_ENABLED=true
KERBEROS_ADMIN_EMAILS=admin@exemple.fr,admin2@exemple.fr

# Pour les tests en local
KERBEROS_SIMULATION_MODE=true
```

### Scénarios d'authentification

| Scénario | Condition | Résultat |
|----------|-----------|----------|
| **Succès** | `REMOTE_USER` correspond à un utilisateur avec un rôle | Connexion automatique |
| **Sans rôle** | `REMOTE_USER` correspond à un utilisateur sans rôle | Formulaire de demande d'accès |
| **Inconnu** | `REMOTE_USER` introuvable en base de données | Accès refusé + notification admin |
| **Sans Kerberos** | `REMOTE_USER` est vide | Formulaire de connexion classique |

### Configuration serveur web

**Apache :**
```apache
<Location />
    AuthType Kerberos
    AuthName "Connexion Kerberos"
    KrbAuthRealm EXEMPLE.FR
    Krb5Keytab /etc/apache2/http.keytab
    KrbMethodNegotiate On
    KrbMethodK5Passwd Off
    require valid-user
</Location>
```

**Nginx :**
```nginx
location / {
    auth_gss on;
    auth_gss_realm EXEMPLE.FR;
    auth_gss_keytab /etc/nginx/http.keytab;
    auth_gss_service_name HTTP;
}
```

Pour les détails complets, voir [INSTALL.md](INSTALL.md).

## Différences avec mary-ui-starter-kit

| Fonctionnalité | [mary-ui-starter-kit](https://github.com/lauroguedes/mary-ui-starter-kit) | Ce projet |
|----------------|---------------------------------------------------------------------------|-----------|
| Gestion des rôles | `laravel-permission` (Spatie) | Simple colonne `role_id` (optionnel via Kerberos) |
| SSO Kerberos | Non | Oui (module optionnel) |
| Complexité | Plus complète | Allégée, plus facile à personnaliser |

## Licence

MIT
