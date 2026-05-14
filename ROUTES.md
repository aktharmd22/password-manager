# SecureVault Routes

Complete URL surface area. All authenticated routes require:

- `auth` — valid session
- `2fa.enabled` — 2FA must be set up
- `2fa.verified` — 2FA must be verified this session

Generated from `php artisan route:list`.

---

## Public / Guest

| Method | URI | Name | Notes |
|---|---|---|---|
| GET    | `/`                          | —                     | Redirects to `/dashboard` (auth) or `/login` |
| GET    | `/login`                     | `login`               | Login form |
| POST   | `/login`                     | —                     | Login submit (rate-limited 10/min) |
| GET    | `/forgot-password`           | `password.request`    | Forgot password form |
| POST   | `/forgot-password`           | `password.email`      | Send reset link |
| GET    | `/reset-password/{token}`    | `password.reset`      | Reset form |
| POST   | `/reset-password`            | `password.store`      | Reset submit |

## Auth (requires session)

| Method | URI | Name |
|---|---|---|
| GET    | `/two-factor/setup`                          | `two-factor.setup` |
| POST   | `/two-factor/setup`                          | `two-factor.setup.store` |
| GET    | `/two-factor/recovery-codes`                 | `two-factor.recovery-codes` |
| POST   | `/two-factor/recovery-codes/acknowledge`     | `two-factor.recovery-codes.acknowledge` |
| GET    | `/two-factor/verify`                         | `two-factor.verify` |
| POST   | `/two-factor/verify`                         | `two-factor.verify.store` (rate-limited 10/min) |
| GET    | `/confirm-password`                          | `password.confirm` |
| POST   | `/confirm-password`                          | — |
| PUT    | `/password`                                  | `password.update` |
| POST   | `/logout`                                    | `logout` |

## Dashboard

| Method | URI | Name |
|---|---|---|
| GET    | `/dashboard`                                 | `dashboard` |

## Credentials

| Method | URI | Name |
|---|---|---|
| GET    | `/credentials`                               | `credentials.index` |
| GET    | `/credentials/create`                        | `credentials.create` |
| POST   | `/credentials`                               | `credentials.store` |
| GET    | `/credentials/{credential}`                  | `credentials.show` |
| GET    | `/credentials/{credential}/edit`             | `credentials.edit` |
| PUT/PATCH | `/credentials/{credential}`               | `credentials.update` |
| DELETE | `/credentials/{credential}`                  | `credentials.destroy` |
| POST   | `/credentials/{credential}/favorite`         | `credentials.favorite` |
| POST   | `/credentials/{credential}/reveal`           | `credentials.reveal` |
| POST   | `/credentials/{credential}/copy`             | `credentials.copy` |
| POST   | `/credentials/bulk-delete`                   | `credentials.bulk-delete` |
| POST   | `/credentials/bulk-export`                   | `credentials.bulk-export` (streamed CSV) |

## Categories

| Method | URI | Name |
|---|---|---|
| GET    | `/categories`                                | `categories.index` |
| GET    | `/categories/create`                         | `categories.create` |
| POST   | `/categories`                                | `categories.store` |
| GET    | `/categories/{category}/edit`                | `categories.edit` |
| PUT/PATCH | `/categories/{category}`                  | `categories.update` |
| DELETE | `/categories/{category}`                     | `categories.destroy` |

## Audit log

| Method | URI | Name |
|---|---|---|
| GET    | `/audit`                                     | `audit.index` |
| GET    | `/audit/export`                              | `audit.export` (streamed CSV, chunked 500/batch) |

## Tools

| Method | URI | Name |
|---|---|---|
| GET    | `/tools/generator`                           | `tools.generator` |
| POST   | `/tools/generator`                           | `tools.generator.api` (JSON) |

## Global search

| Method | URI | Name |
|---|---|---|
| GET    | `/search?q=…`                                | `search` (JSON) |

## Settings

| Method | URI | Name |
|---|---|---|
| GET    | `/settings`                                  | — (redirect to profile) |
| GET    | `/settings/profile`                          | `settings.profile` |
| PATCH  | `/settings/profile`                          | `settings.profile.update` |
| GET    | `/settings/security`                         | `settings.security` |
| PATCH  | `/settings/security/password`                | `settings.security.password` (requires current pw + 2FA code) |
| POST   | `/settings/security/2fa/regenerate`          | `settings.security.2fa.regenerate` |
| GET    | `/settings/preferences`                      | `settings.preferences` |
| PATCH  | `/settings/preferences`                      | `settings.preferences.update` (no-op, localStorage on client) |
| GET    | `/settings/backup`                           | `settings.backup` |
| POST   | `/settings/backup/export`                    | `settings.backup.export` (returns `.svault`) |
| POST   | `/settings/backup/import`                    | `settings.backup.import` |

## JSON endpoint examples

### `POST /tools/generator`
```json
// Request
{ "length": 24, "uppercase": true, "lowercase": true, "numbers": true, "symbols": true, "exclude_similar": true }

// Response
{ "password": "...", "strength": { "score": 4, "label": "Excellent" } }
```

### `POST /credentials/{id}/copy`
```json
// Request
{ "field": "password" }   // password | username | email | url

// Response
{ "value": "<plaintext>", "clear_after": 30 }   // null for non-password fields
```

### `POST /credentials/{id}/reveal`
```json
// Response
{ "password": "<plaintext>", "reveal_seconds": 10 }
```

### `GET /search?q=foo`
```json
// Response
[
  {
    "id": 1,
    "title": "Test admin Gmail",
    "subtitle": "admin@example.com",
    "category": "Gmail",
    "category_icon": "mail",
    "category_color": "#EA4335",
    "url": "http://localhost:8000/credentials/1"
  }
]
```

---

## Total: 52 routes
