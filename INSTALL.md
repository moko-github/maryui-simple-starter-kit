# Guide d'installation

## Démarrage rapide

```bash
composer create-project moko-github/maryui-simple-starter-kit mon-app
cd mon-app
npm install && npm run build
```

Le programme d'installation vous posera quelques questions pour configurer les modules optionnels.

---

## Modules optionnels

### Authentification Kerberos (SSO)

Lors de l'installation, vous pouvez activer le support de la connexion unique (Single Sign-On) via Kerberos.

Le module est fourni par le package [`moko-github/kerberos-auth`](https://github.com/moko-github/kerberos-auth), déjà inclus dans les dépendances du starter kit. Aucun fichier source Kerberos n'est copié dans votre application.

**Ce que fait l'installateur automatiquement :**

- Lance `php artisan kerberos:install` (fourni par le package), qui :
  - enregistre le middleware Kerberos et ajoute les routes (`/demande-acces`, `/acces-refuse`)
  - adapte le modèle `User` (champs `kerberos` et `role_id`)
  - ajoute les variables Kerberos dans votre fichier `.env`
  - exécute les migrations et initialise la table des rôles (`Admin`, `User`)
- Publie la configuration `config/kerberos.php` et y définit le layout `layouts.auth`
- Injecte le widget de simulation dans la page de connexion et le bandeau de simulation dans la barre latérale
- Ajoute le champ `Kerberos` aux écrans de gestion des utilisateurs (liste, création, édition)

**Après l'installation, configurez votre `.env` :**

```env
# Activer Kerberos (mettre à true en production)
KERBEROS_ENABLED=true

# Adresses email des administrateurs pour les notifications (séparées par des virgules)
KERBEROS_ADMIN_EMAILS=admin@exemple.fr,admin2@exemple.fr

# Pour le développement local, activer le widget de simulation
KERBEROS_SIMULATION_MODE=true
```

**Les 4 scénarios d'authentification :**

| Scénario | Condition | Résultat |
|----------|-----------|----------|
| **Succès** | `REMOTE_USER` correspond à un utilisateur avec un rôle | Connexion automatique |
| **Sans rôle** | `REMOTE_USER` correspond à un utilisateur sans rôle | Formulaire de demande d'accès |
| **Inconnu** | `REMOTE_USER` introuvable en base de données | Accès refusé + notification admin |
| **Sans Kerberos** | `REMOTE_USER` est vide | Formulaire de connexion classique |

**Configuration du serveur web (exemple Apache) :**

```apache
<VirtualHost *:443>
    ServerName monapp.exemple.fr

    <Location />
        AuthType Kerberos
        AuthName "Connexion Kerberos"
        KrbAuthRealm EXEMPLE.FR
        Krb5Keytab /etc/apache2/http.keytab
        KrbMethodNegotiate On
        KrbMethodK5Passwd Off
        require valid-user
    </Location>
</VirtualHost>
```

**Exemple Nginx :**

```nginx
server {
    listen 443 ssl;
    server_name monapp.exemple.fr;

    location / {
        auth_gss on;
        auth_gss_realm EXEMPLE.FR;
        auth_gss_keytab /etc/nginx/http.keytab;
        auth_gss_service_name HTTP;
    }
}
```

---

## Relancer l'installateur manuellement

Si vous avez ignoré un module lors de l'installation, vous pouvez relancer l'installateur :

```bash
php artisan app:install
```

---

## Développement

```bash
# Démarrer le serveur de développement
composer run dev

# Lancer les tests
php artisan test

# Formatage du code
vendor/bin/pint
```
