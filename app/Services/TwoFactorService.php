<?php

namespace App\Services;

use App\Models\User;
use BaconQrCode\Renderer\ImageRenderer;
use BaconQrCode\Renderer\Image\SvgImageBackEnd;
use BaconQrCode\Renderer\RendererStyle\RendererStyle;
use BaconQrCode\Writer;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use PragmaRX\Google2FA\Google2FA;

class TwoFactorService
{
    public function __construct(private readonly Google2FA $google2fa)
    {
    }

    /**
     * Generate a fresh 2FA secret for the user. Does NOT save it; the caller
     * is responsible for persisting after setup is confirmed via verifyCode().
     */
    public function generateSecret(): string
    {
        return $this->google2fa->generateSecretKey(32);
    }

    /**
     * Verify a TOTP code against a secret. Allows ±1 window (≈30s) of clock drift.
     */
    public function verifyCode(string $secret, string $code): bool
    {
        $code = trim(str_replace(' ', '', $code));
        if ($code === '' || ! ctype_digit($code) || strlen($code) !== 6) {
            return false;
        }

        return $this->google2fa->verifyKey($secret, $code, 1);
    }

    /**
     * Build the otpauth:// URI for QR-code rendering.
     */
    public function otpauthUrl(User $user, string $secret): string
    {
        $issuer = rawurlencode(config('app.name', 'SecureVault'));
        $label = rawurlencode($issuer . ':' . $user->email);

        return sprintf(
            'otpauth://totp/%s?secret=%s&issuer=%s&algorithm=SHA1&digits=6&period=30',
            $label,
            $secret,
            $issuer,
        );
    }

    /**
     * Render the otpauth URL as an inline SVG QR code (no PNG dep needed).
     */
    public function qrCodeSvg(string $otpauthUrl, int $size = 220): string
    {
        $renderer = new ImageRenderer(
            new RendererStyle($size, 0),
            new SvgImageBackEnd(),
        );

        return (new Writer($renderer))->writeString($otpauthUrl);
    }

    /**
     * Generate N recovery codes (random 10-char hex strings, formatted xxxxx-xxxxx).
     * Returns plaintext codes — store them hashed via setRecoveryCodes().
     *
     * @return array<int, string>
     */
    public function generateRecoveryCodes(int $count = null): array
    {
        $count ??= (int) config('vault.recovery_codes_count', 10);

        $codes = [];
        for ($i = 0; $i < $count; $i++) {
            $raw = bin2hex(random_bytes(5)); // 10 hex chars
            $codes[] = sprintf('%s-%s', substr($raw, 0, 5), substr($raw, 5, 5));
        }

        return $codes;
    }

    /**
     * Convert an array of plaintext recovery codes into their hashed form for storage.
     *
     * @param  array<int, string>  $plainCodes
     * @return array<int, string>  Each entry is a bcrypt hash of the corresponding code.
     */
    public function hashRecoveryCodes(array $plainCodes): array
    {
        return array_map(fn (string $code) => Hash::make($code), $plainCodes);
    }

    /**
     * Check a submitted recovery code against the user's stored hashes.
     * Returns the index of the matched code (so the caller can consume it),
     * or null if none match.
     */
    public function findMatchingRecoveryCode(User $user, string $submitted): ?int
    {
        $submitted = trim($submitted);
        $codes = $user->two_factor_recovery_codes ?: [];

        foreach ($codes as $index => $hash) {
            if (Hash::check($submitted, $hash)) {
                return $index;
            }
        }

        return null;
    }

    /**
     * Mark a recovery code as consumed by removing it from the user's stored list.
     */
    public function consumeRecoveryCode(User $user, int $index): void
    {
        $codes = $user->two_factor_recovery_codes ?: [];
        unset($codes[$index]);

        $user->two_factor_recovery_codes = array_values($codes);
        $user->save();
    }
}
