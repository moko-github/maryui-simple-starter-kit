# Installation Guide

## Quick Start

```bash
composer create-project moko-github/maryui-simple-starter-kit my-app
cd my-app
npm install && npm run build
```

The installer will ask you a few questions to configure optional modules.

---

## Optional Modules

### Kerberos Authentication (SSO)

When prompted during installation, you can enable Kerberos Single Sign-On support.

**What the installer does automatically:**

- Copies the Kerberos module files (models, services, middleware, Livewire components, migrations)
- Registers the `KerberosAuthentication` middleware in `bootstrap/app.php`
- Adds `kerberos` and `role_id` fields to the `User` model
- Injects the simulation widget into the login page
- Adds Kerberos routes (`/access-request`, `/access-denied`)
- Schedules the daily cleanup of authentication logs
- Appends Kerberos variables to your `.env` file
- Runs migrations and seeds the roles table (`Admin`, `User`)

**After installation, configure your `.env`:**

```env
# Enable Kerberos (set to true in production)
KERBEROS_ENABLED=true

# Admin email addresses for notifications (comma-separated)
KERBEROS_ADMIN_EMAILS=admin@example.com,admin2@example.com

# For local development, enable the simulation widget
KERBEROS_SIMULATION_MODE=true
```

**The 4 authentication scenarios:**

| Scenario | Condition | Result |
|----------|-----------|--------|
| **Success** | `REMOTE_USER` matches a user with a role | Automatic login |
| **No role** | `REMOTE_USER` matches a user without a role | Access request form |
| **Unknown** | `REMOTE_USER` not found in database | Access denied + admin notification |
| **No Kerberos** | `REMOTE_USER` is empty | Standard login form |

**Web server configuration (Apache example):**

```apache
<VirtualHost *:443>
    ServerName myapp.example.com

    <Location />
        AuthType Kerberos
        AuthName "Kerberos Login"
        KrbAuthRealm EXAMPLE.COM
        Krb5Keytab /etc/apache2/http.keytab
        KrbMethodNegotiate On
        KrbMethodK5Passwd Off
        require valid-user
    </Location>
</VirtualHost>
```

**Nginx example:**

```nginx
server {
    listen 443 ssl;
    server_name myapp.example.com;

    location / {
        auth_gss on;
        auth_gss_realm EXAMPLE.COM;
        auth_gss_keytab /etc/nginx/http.keytab;
        auth_gss_service_name HTTP;
    }
}
```

---

## Running the installer manually

If you skipped a module during installation, you can re-run the installer:

```bash
php artisan app:install
```

---

## Development

```bash
# Start the development server
composer run dev

# Run tests
php artisan test

# Code formatting
vendor/bin/pint
```
