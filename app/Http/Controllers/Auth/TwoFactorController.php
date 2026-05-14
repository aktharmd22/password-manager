<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Services\AuditService;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class TwoFactorController extends Controller
{
    public function __construct(
        private readonly TwoFactorService $twoFactor,
        private readonly AuditService $audit,
    ) {}

    // ---------------------------------------------------------------------
    // Setup flow (mandatory on first login)
    // ---------------------------------------------------------------------

    public function setup(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user->hasTwoFactorEnabled()) {
            return redirect()->route('dashboard');
        }

        // Generate a secret and stash it in session until the user confirms a code.
        // We don't persist the secret until verification — that way a half-finished
        // setup can be re-started fresh without leaving an unusable secret on disk.
        $secret = $request->session()->get('vault.2fa_pending_secret');
        if (! $secret) {
            $secret = $this->twoFactor->generateSecret();
            $request->session()->put('vault.2fa_pending_secret', $secret);
        }

        $otpauthUrl = $this->twoFactor->otpauthUrl($user, $secret);
        $qrSvg = $this->twoFactor->qrCodeSvg($otpauthUrl);

        return view('auth.two-factor-setup', [
            'secret' => $secret,
            'qrSvg' => $qrSvg,
        ]);
    }

    public function setupStore(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6'],
        ]);

        $user = $request->user();
        $pendingSecret = $request->session()->get('vault.2fa_pending_secret');

        if (! $pendingSecret) {
            return redirect()->route('two-factor.setup')
                ->with('error', 'Your 2FA setup session expired. Please try again.');
        }

        if (! $this->twoFactor->verifyCode($pendingSecret, $request->input('code'))) {
            throw ValidationException::withMessages([
                'code' => 'That code doesn\'t match. Make sure your device clock is set to network time, then try again.',
            ]);
        }

        // Generate plaintext codes once, show them to the user, store only the hashes.
        $plainRecoveryCodes = $this->twoFactor->generateRecoveryCodes();
        $hashedCodes = $this->twoFactor->hashRecoveryCodes($plainRecoveryCodes);

        $user->forceFill([
            'two_factor_secret' => $pendingSecret,
            'two_factor_recovery_codes' => $hashedCodes,
            'two_factor_confirmed_at' => now(),
            'two_factor_enabled' => true,
        ])->save();

        // The user is now "2FA verified" for the rest of this session.
        $request->session()->forget('vault.2fa_pending_secret');
        $request->session()->put('vault.2fa_verified', true);
        $request->session()->put('vault.recovery_codes_to_show', $plainRecoveryCodes);

        $this->audit->log('2fa_enabled', $user);

        return redirect()->route('two-factor.recovery-codes');
    }

    public function recoveryCodes(Request $request): View|RedirectResponse
    {
        $codes = $request->session()->get('vault.recovery_codes_to_show');

        if (! $codes) {
            return redirect()->route('dashboard');
        }

        return view('auth.recovery-codes', ['codes' => $codes]);
    }

    public function recoveryCodesAcknowledge(Request $request): RedirectResponse
    {
        $request->session()->forget('vault.recovery_codes_to_show');
        return redirect()->route('dashboard');
    }

    // ---------------------------------------------------------------------
    // Verify flow (every login when 2FA is enabled)
    // ---------------------------------------------------------------------

    public function verify(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if (! $user->hasTwoFactorEnabled()) {
            return redirect()->route('two-factor.setup');
        }

        if ($request->session()->get('vault.2fa_verified', false)) {
            return redirect()->intended(route('dashboard'));
        }

        return view('auth.two-factor-verify');
    }

    public function verifyStore(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['nullable', 'string'],
            'recovery_code' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $code = trim((string) $request->input('code'));
        $recovery = trim((string) $request->input('recovery_code'));

        if ($code === '' && $recovery === '') {
            throw ValidationException::withMessages([
                'code' => 'Enter a 6-digit code from your authenticator app or a recovery code.',
            ]);
        }

        // Try TOTP first if provided.
        if ($code !== '') {
            if (! $this->twoFactor->verifyCode($user->two_factor_secret, $code)) {
                $this->audit->log('failed_login', $user, metadata: ['reason' => 'invalid_2fa_code']);
                throw ValidationException::withMessages(['code' => 'Invalid authentication code.']);
            }
        } else {
            $matchIndex = $this->twoFactor->findMatchingRecoveryCode($user, $recovery);
            if ($matchIndex === null) {
                $this->audit->log('failed_login', $user, metadata: ['reason' => 'invalid_recovery_code']);
                throw ValidationException::withMessages(['recovery_code' => 'Invalid recovery code.']);
            }
            // Recovery codes are single-use — consume immediately on match.
            $this->twoFactor->consumeRecoveryCode($user, $matchIndex);
            $request->session()->flash('info', 'Recovery code consumed. Generate a new set in Settings → Security.');
        }

        $request->session()->put('vault.2fa_verified', true);
        $user->forceFill(['last_login_at' => now(), 'last_login_ip' => $request->ip()])->save();
        $this->audit->log('login', $user);

        return redirect()->intended(route('dashboard'));
    }

    // ---------------------------------------------------------------------
    // Settings: regenerate codes / disable 2FA (re-auth required in controller)
    // ---------------------------------------------------------------------

    public function regenerateRecoveryCodes(Request $request): RedirectResponse
    {
        $user = $request->user();
        $plainCodes = $this->twoFactor->generateRecoveryCodes();
        $user->forceFill([
            'two_factor_recovery_codes' => $this->twoFactor->hashRecoveryCodes($plainCodes),
        ])->save();

        $request->session()->put('vault.recovery_codes_to_show', $plainCodes);
        $this->audit->log('2fa_regenerated', $user);

        return redirect()->route('two-factor.recovery-codes');
    }
}
