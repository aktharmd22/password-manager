<?php

namespace App\Services;

use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Support\Facades\Crypt;

/**
 * Thin wrapper around Laravel's Crypt facade that gives SecureVault a single
 * choke-point for every encrypt/decrypt operation. By routing through this
 * service we get:
 *   - One place to swap algorithms if we ever migrate off AES-256-CBC
 *   - Consistent handling of nullable plaintext (encrypt(null) returns null)
 *   - JSON-safe helpers for credential custom fields
 *   - Guarantee no decryption call ever logs the plaintext value
 *
 * Laravel's Crypt is keyed off APP_KEY. Rotate APP_KEY only via
 * `php artisan key:rotate` (and re-encrypt all rows), never by hand —
 * doing so will silently brick every credential in the vault.
 */
class EncryptionService
{
    /**
     * Encrypt a plaintext string. Returns null for null input so callers can
     * pipe nullable fields through without branching.
     */
    public function encrypt(?string $plaintext): ?string
    {
        if ($plaintext === null || $plaintext === '') {
            return null;
        }

        return Crypt::encryptString($plaintext);
    }

    /**
     * Decrypt a ciphertext string. Returns null for null input. Throws
     * DecryptException if the ciphertext is malformed or the key has rotated.
     */
    public function decrypt(?string $ciphertext): ?string
    {
        if ($ciphertext === null || $ciphertext === '') {
            return null;
        }

        return Crypt::decryptString($ciphertext);
    }

    /**
     * Decrypt and return null on any failure (use only where a partial UI
     * render is preferable to an exception, e.g. listing legacy rows).
     */
    public function decryptOrNull(?string $ciphertext): ?string
    {
        try {
            return $this->decrypt($ciphertext);
        } catch (DecryptException) {
            return null;
        }
    }

    /**
     * Encrypt a JSON-serializable value (typically an array of custom fields).
     */
    public function encryptJson(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        return Crypt::encryptString(json_encode($value, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE));
    }

    /**
     * Decrypt a JSON ciphertext. Returns the decoded array, or [] on null.
     *
     * @return array<int|string, mixed>
     */
    public function decryptJson(?string $ciphertext): array
    {
        $plaintext = $this->decrypt($ciphertext);

        if ($plaintext === null) {
            return [];
        }

        try {
            $decoded = json_decode($plaintext, true, 512, JSON_THROW_ON_ERROR);
        } catch (\JsonException) {
            return [];
        }

        return is_array($decoded) ? $decoded : [];
    }
}
