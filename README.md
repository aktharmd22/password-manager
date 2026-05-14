# SecureVault

An internal, single-user password vault built on Laravel 11. Stores company
credentials (Gmail, servers, websites, databases, FTP, API keys, …) with
application-level AES-256 encryption, mandatory 2FA, a full audit log, and
an encrypted backup/restore flow.

This is not a SaaS — it is a **self-hosted internal tool** for one admin user.

---

## Highlights

- **Encryption at rest** — every password, note, and custom field is stored as
  AES-256-CBC ciphertext keyed off `APP_KEY`
- **Mandatory 2FA** (TOTP) with 10 single-use recovery codes
- **Append-only audit log** for every action — login, view, reveal, copy,
  edit, delete, export, 2FA enable/regenerate, failed login
- **Auto-clearing clipboard** — copied passwords wipe after 30s
- **Auto-hiding password reveal** — visible for 10s then re-hides
- **Idle-timeout auto-logout** — 10 minutes of inactivity ends the session
- **Encrypted backup/restore** — AES-256-GCM with PBKDF2-SHA256 (200k iters),
  passphrase-protected `.svault` files
- **CSP, X-Frame-Options DENY, strict same-site cookies, HSTS** in production
- **Optional IP allow-list** for an extra layer of access control

---

## Tech Stack

- Laravel 11.x (PHP 8.2+)
- MySQL 8 / MariaDB 10.4+
- Tailwind CSS 3 + Alpine.js
- DM Sans (Google Fonts) + Lucide icons (CDN)
- pragmarx/google2fa + bacon/bacon-qr-code

---

## Setup

### Prerequisites

- PHP 8.2 or newer with the `openssl`, `mbstring`, `pdo_mysql`, and `gd` (or
  `imagick`) extensions
- Composer 2.x
- Node.js 20+ and npm 10+
- MySQL 8 (or MariaDB 10.4+) accessible at `127.0.0.1:3306`

### Install

```bash
git clone <your-repo-url> securevault
cd securevault

composer install
npm install

cp .env.example .env
php artisan key:generate
```

### Database

Create the database, then run migrations + seeders:

```sql
CREATE DATABASE securevault CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

```bash
php artisan migrate
php artisan db:seed
```

The seeder will print a one-time admin password to the console:

```
========================================================================
  Email:    eliteboxindia@gmail.com
  Password: <random 24-char password>
========================================================================
  Save this password NOW. It will not be shown again.
```

Copy it. You'll be forced to set up 2FA on first login.

### Run

```bash
npm run build              # production assets
php artisan serve          # http://localhost:8000
```

For development with hot reload:

```bash
npm run dev                # in one terminal
php artisan serve          # in another
```

### First Login

1. Visit <http://localhost:8000>
2. Sign in with `eliteboxindia@gmail.com` + the seeded password
3. Scan the QR code with **Google Authenticator**, **Authy**, **1Password**,
   or any TOTP app
4. Enter the 6-digit code → **10 recovery codes are shown once**. Save them
   (copy/download). They're hashed on the server; you cannot view them again.
5. Land on the dashboard.

### Changing the admin email/password

Edit [database/seeders/AdminUserSeeder.php](database/seeders/AdminUserSeeder.php)
before seeding, or use `Settings → Security` (requires current password +
valid 2FA code) after logging in.

---

## Deployment

### Production checklist

- [ ] `APP_ENV=production` and `APP_DEBUG=false`
- [ ] `APP_KEY` set and **backed up to an offline location** (a password
      manager you control, an encrypted USB stick, a sealed envelope, …)
- [ ] Serve behind HTTPS — set `SESSION_SECURE_COOKIE=true` and
      `VAULT_FORCE_HTTPS=true`
- [ ] `php artisan config:cache && php artisan route:cache && php artisan view:cache`
- [ ] `npm run build` (commit the resulting `public/build/` or build on deploy)
- [ ] Web server (nginx/Apache) configured to never serve files outside `public/`
- [ ] MySQL backups configured separately (the encrypted vault is only as
      durable as the database backup behind it)
- [ ] Set `VAULT_IP_WHITELIST_ENABLED=true` + `VAULT_IP_WHITELIST` if access
      should be restricted to office IPs
- [ ] Tighten the CSP in
      [`SecurityHeaders.php`](app/Http/Middleware/SecurityHeaders.php) once
      you no longer use the Lucide / Google Fonts CDNs

### APP_KEY backup procedure

**This is the single most important file in the system.** Losing it means
every credential becomes unreadable ciphertext — there is no recovery.

1. After `php artisan key:generate`, immediately copy the `APP_KEY=` line out
   of `.env` to **two offline locations**:
   - A printed sheet in a locked cabinet
   - An encrypted USB stick in a different physical location
2. Document the key fingerprint somewhere recoverable (e.g. last 6 chars) so
   you can quickly verify a restored key matches.
3. Never commit `.env` to git (already in `.gitignore`).
4. If you must rotate `APP_KEY`, use Laravel's `php artisan key:rotate` —
   never edit the key by hand.

---

## Architecture

```
app/
├── Models/
│   ├── User.php                 # 2FA fields, last-login tracking
│   ├── Credential.php           # Encrypted password/notes/custom fields
│   ├── Category.php             # Icon + color + sort order
│   ├── AuditLog.php             # Append-only
│   └── PasswordHistory.php
├── Services/
│   ├── EncryptionService.php    # Crypt facade wrapper, null-safe, JSON helpers
│   ├── PasswordGeneratorService.php
│   ├── AuditService.php         # Never throws; strips sensitive metadata
│   ├── TwoFactorService.php     # TOTP + QR SVG + recovery codes
│   └── BackupService.php        # AES-256-GCM + PBKDF2-SHA256
├── Http/
│   ├── Controllers/
│   │   ├── DashboardController.php
│   │   ├── CredentialController.php
│   │   ├── CategoryController.php
│   │   ├── AuditLogController.php
│   │   ├── SettingsController.php
│   │   ├── PasswordGeneratorController.php
│   │   ├── GlobalSearchController.php
│   │   └── Auth/
│   │       ├── AuthenticatedSessionController.php   # Audit + 2FA branching
│   │       └── TwoFactorController.php              # Setup, verify, regen
│   ├── Middleware/
│   │   ├── EnsureTwoFactorEnabled.php       # alias: 2fa.enabled
│   │   ├── EnsureTwoFactorVerified.php      # alias: 2fa.verified
│   │   ├── LogActivity.php                  # alias: log.activity
│   │   ├── SecurityHeaders.php              # global
│   │   ├── ForceHttps.php                   # global
│   │   └── IpWhitelist.php                  # global
│   └── Requests/
│       ├── StoreCredentialRequest.php
│       ├── UpdateCredentialRequest.php
│       └── StoreCategoryRequest.php
└── Policies/
    └── CredentialPolicy.php
```

Encryption flow:

```
plaintext password ──→ EncryptionService::encrypt()
                       └─ Crypt::encryptString()  ──→ ciphertext (TEXT column)

ciphertext         ──→ EncryptionService::decrypt()
                       └─ Crypt::decryptString()  ──→ plaintext (volatile)
```

Plaintext is only computed inside controllers when responding to:

- `/credentials/{id}/reveal` (audited as `revealed`)
- `/credentials/{id}/copy`   (audited as `copied_password`)
- `/credentials/bulk-export` (audited as `exported`)
- `/settings/backup/export`  (audited as `exported`, encrypted with passphrase)

It is never written to disk or logs.

---

## Useful commands

```bash
# Smoke checks
php artisan route:list                    # See all 52 routes
php artisan tinker                        # Drop into the REPL

# Reset the database (DROPS ALL CREDENTIALS — be sure)
php artisan migrate:fresh --seed

# Clear caches
php artisan optimize:clear

# Run the test suite (built-in Laravel tests, no SecureVault tests yet)
php artisan test
```

---

## Routes

See [ROUTES.md](ROUTES.md) for the full URL surface area.

---

## Security

See [SECURITY.md](SECURITY.md) for the threat model and details on
encryption choices, audit guarantees, and what backups do/don't cover.

---

## License

Internal use only.
