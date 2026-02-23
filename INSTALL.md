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

**Ce que fait l'installateur automatiquement :**

- Copie les fichiers du module Kerberos (modèles, services, middleware, composants Livewire, migrations)
- Enregistre le middleware `KerberosAuthentication` dans `bootstrap/app.php`
- Ajoute les champs `kerberos` et `role_id` au modèle `User`
- Injecte le widget de simulation dans la page de connexion
- Ajoute les routes Kerberos (`/demande-acces`, `/acces-refuse`)
- Planifie le nettoyage quotidien des journaux d'authentification
- Ajoute les variables Kerberos dans votre fichier `.env`
- Exécute les migrations et initialise la table des rôles (`Admin`, `User`)

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
