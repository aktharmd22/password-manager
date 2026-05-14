# SecureVault Security Model

This document describes what SecureVault protects against, what it does
not, and exactly how the cryptography is configured. Read this before
deploying to production.

---

## Threat model

### In scope

| Threat | Mitigation |
|---|---|
| Stolen database dump | All credential fields encrypted with `APP_KEY`; dump alone is useless |
| Brute-forced master password | bcrypt (12 rounds) + 5/min rate limit + mandatory 2FA |
| Phished master password | 2FA still required → attacker also needs TOTP secret |
| Replay of a captured login cookie | `SESSION_ENCRYPT=true`, 15-min lifetime, `SESSION_EXPIRE_ON_CLOSE=true`, strict same-site cookie |
| Casual shoulder surfing | Passwords masked by default; reveal auto-hides after 10s |
| Clipboard left populated | Auto-cleared 30s after copy |
| Insider abuse | Append-only audit log records every reveal, copy, export, edit, delete |
| Stolen .svault backup | Encrypted with passphrase-derived key, independent of APP_KEY |
| CSRF | Laravel CSRF token on every form + same-site strict cookie |
| Click-jacking | `X-Frame-Options: DENY` + CSP `frame-ancestors 'none'` |
| Unintended public exposure | No public-registration route; sign-in is the only entry point |

### Out of scope

| Threat | Why |
|---|---|
| Compromised server with `APP_KEY` | Whoever holds `APP_KEY` can decrypt the database |
| Compromised admin laptop | Browser autofill / process memory beyond our control |
| Physical access to a logged-in machine | The 15-min idle logout helps but isn't perfect |
| MITM on the wire | Deploy behind HTTPS; SecureVault doesn't do TLS itself |
| Backup file leaked + weak passphrase | Choose a strong passphrase ≥ 12 chars |

---

## Cryptography

### Credential fields at rest

- **Algorithm:** AES-256-CBC with HMAC-SHA256 (Laravel `Crypt::encryptString`)
- **Key:** `APP_KEY` (32 bytes, base64-encoded in `.env`)
- **IV:** per-call random 16 bytes (handled by Crypt)
- **Authentication:** HMAC-SHA256 (Crypt verifies before returning plaintext)
- **Encrypted columns:**
  - `credentials.password_encrypted`
  - `credentials.notes_encrypted`
  - `credentials.custom_fields_encrypted`
  - `password_histories.old_password_encrypted`
  - `users.two_factor_secret` (Laravel `encrypted` cast)
  - `users.two_factor_recovery_codes` are bcrypt-hashed (one-way)

The encryption path lives in [`EncryptionService`](app/Services/EncryptionService.php).
This is the only choke-point — every controller routes through it.

### 2FA

- **Algorithm:** TOTP-SHA1 with 30-second period, 6-digit code
  (RFC 6238 standard — matches Google Authenticator, Authy, 1Password, etc.)
- **Secret length:** 32 bytes (256 bits)
- **Time window:** ±1 step (≈30 s clock drift tolerance)
- **Storage:** Secret is AES-encrypted on disk (Laravel `encrypted` cast)
- **Recovery codes:** 10 codes, 10 hex characters each, formatted as
  `xxxxx-xxxxx`. Codes are bcrypt-hashed at rest — the plaintext is shown
  exactly once and then unrecoverable. Each code is single-use.

### Backup files (`.svault`)

- **Algorithm:** AES-256-GCM (authenticated encryption)
- **KDF:** PBKDF2-HMAC-SHA256, 200,000 iterations
- **Salt:** 16 random bytes per backup
- **IV:** 12 random bytes per backup
- **Auth tag:** 16 bytes
- **Format:** base64-encoded JSON wrapper with all parameters embedded

A backup file is **independent of `APP_KEY`** — restoring on a fresh
install with a new `APP_KEY` works, because the backup decrypts to
plaintext credentials which are then re-encrypted with the new key.

This also means: **back up `APP_KEY` separately**. A `.svault` file does
not include it.

### Master password

- **Storage:** bcrypt (12 rounds)
- **Policy on change:** min 12 chars, mixed case, numbers, symbols, and
  Laravel's `Password::uncompromised()` rule (Have-I-Been-Pwned k-anonymity check)
- **Rate limiting:** 5 failed attempts per email+IP combination per minute
  triggers a lockout

---

## Audit log guarantees

`audit_logs` is **append-only**:

- The Eloquent model disables `updated_at`
- The migration has no UPDATE/DELETE policy from controllers
- Failed audit writes are silently swallowed (we never block a user action
  on audit logging) but reported via `report($e)` to your `LOG_CHANNEL`
- Metadata is sanitized — keys named `password`, `secret`, `token`,
  `plaintext`, `notes`, `custom_fields` are stripped before the row is saved

Actions logged: `created`, `viewed`, `updated`, `deleted`, `restored`,
`copied_password`, `copied_username`, `revealed`, `exported`, `imported`,
`login`, `logout`, `failed_login`, `2fa_enabled`, `2fa_disabled`,
`2fa_regenerated`, `password_changed`.

---

## Session security

- `SESSION_LIFETIME=15` minutes (server-side)
- `SESSION_EXPIRE_ON_CLOSE=true` — browser close kills the session
- `SESSION_ENCRYPT=true` — session payload encrypted in the DB
- `SESSION_SAME_SITE=strict` — cookie is not sent on cross-origin requests
- `SESSION_SECURE_COOKIE=true` (production) — cookie only over HTTPS
- Session ID regenerated on successful login (prevents fixation)
- JS-side idle-timeout fires `POST /logout` after 10 minutes of no
  mouse/keyboard input

---

## HTTP response headers

Every response includes:

```
Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval' https://unpkg.com; style-src 'self' 'unsafe-inline' https://fonts.googleapis.com https://fonts.bunny.net; font-src 'self' https://fonts.gstatic.com https://fonts.bunny.net data:; img-src 'self' data: blob:; connect-src 'self'; frame-ancestors 'none'; base-uri 'self'; form-action 'self'
X-Content-Type-Options: nosniff
X-Frame-Options: DENY
Referrer-Policy: no-referrer
Permissions-Policy: camera=(), microphone=(), geolocation=()
Strict-Transport-Security: max-age=31536000; includeSubDomains; preload   (production only)
```

The CSP is intentionally lenient (`unsafe-inline`, `unsafe-eval`,
`unpkg.com`) because the UI relies on Alpine.js and the Lucide CDN.
For maximum hardening:

1. Replace the Lucide CDN with the npm `lucide` package (bundled by Vite)
2. Remove all Alpine `@click` / `x-data` inline expressions in favor of
   pre-registered components
3. Tighten `script-src` to `'self'` and `'nonce-…'`

---

## What this app does NOT do

- It does **not** rotate `APP_KEY` automatically.
- It does **not** encrypt the database connection — use TLS to MySQL in
  production.
- It does **not** detect cloned authenticator apps. If you suspect a
  device is compromised, use `Settings → Security → Regenerate codes` and
  then enroll a new TOTP secret via re-setup.
- It does **not** include intrusion detection. Watch the audit log for
  suspicious `failed_login` patterns.

---

## Reporting a vulnerability

This is an internal tool. If you find a security issue, report it
directly to the SecureVault maintainer in person or over an encrypted
channel — do not file a public issue.
