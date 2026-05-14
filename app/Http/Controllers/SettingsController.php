<?php

namespace App\Http\Controllers;

use App\Services\AuditService;
use App\Services\BackupService;
use App\Services\TwoFactorService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\Response;

class SettingsController extends Controller
{
    public function __construct(
        private readonly AuditService $audit,
        private readonly BackupService $backupService,
        private readonly TwoFactorService $twoFactor,
    ) {}

    // ---------------------------------------------------------------------
    // Profile
    // ---------------------------------------------------------------------
    public function profile(Request $request): View
    {
        return view('settings.profile', ['user' => $request->user()]);
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255',
                Rule::unique('users')->ignore($request->user()->id)],
        ]);

        $request->user()->fill($validated)->save();

        return back()->with('success', 'Profile updated.');
    }

    // ---------------------------------------------------------------------
    // Security
    // ---------------------------------------------------------------------
    public function security(Request $request): View
    {
        return view('settings.security', [
            'user' => $request->user(),
            'recoveryCodesToShow' => $request->session()->get('vault.recovery_codes_to_show'),
        ]);
    }

    public function updatePassword(Request $request): RedirectResponse
    {
        $request->validate([
            'current_password' => ['required', 'current_password'],
            'two_factor_code' => ['required', 'string', 'size:6'],
            'password' => [
                'required', 'confirmed',
                Password::min(config('vault.password_policy.min_length'))
                    ->letters()->mixedCase()->numbers()->symbols()->uncompromised(),
            ],
        ]);

        $user = $request->user();

        if (! $this->twoFactor->verifyCode($user->two_factor_secret, $request->input('two_factor_code'))) {
            throw ValidationException::withMessages(['two_factor_code' => 'Invalid 2FA code.']);
        }

        $user->forceFill(['password' => Hash::make($request->input('password'))])->save();
        $this->audit->log('password_changed', $user);

        return back()->with('success', 'Master password updated.');
    }

    // ---------------------------------------------------------------------
    // Preferences (purely client-side via localStorage; we just render the page)
    // ---------------------------------------------------------------------
    public function preferences(Request $request): View
    {
        return view('settings.preferences');
    }

    public function updatePreferences(Request $request): RedirectResponse
    {
        // No-op — preferences are stored in localStorage on the client. This
        // route exists so any progressive enhancement (e.g. moving prefs to
        // a DB column) can later be wired in without changing routes.
        return back()->with('success', 'Preferences saved locally.');
    }

    // ---------------------------------------------------------------------
    // Backup
    // ---------------------------------------------------------------------
    public function backup(Request $request): View
    {
        return view('settings.backup');
    }

    public function exportBackup(Request $request): Response
    {
        $request->validate([
            'passphrase' => ['required', 'string', 'min:12'],
            'passphrase_confirmation' => ['required', 'same:passphrase'],
            'current_password' => ['required', 'current_password'],
        ]);

        $payload = $this->backupService->buildPayload();
        $encrypted = $this->backupService->encryptBackup($payload, $request->input('passphrase'));

        $this->audit->log('exported', $request->user(), null, [
            'count' => count($payload['credentials']),
            'format' => 'encrypted-json',
        ]);

        $filename = 'securevault-backup-' . now()->format('Ymd-His') . '.svault';

        return response($encrypted, 200, [
            'Content-Type' => 'application/octet-stream',
            'Content-Disposition' => 'attachment; filename="' . $filename . '"',
        ]);
    }

    public function importBackup(Request $request): RedirectResponse
    {
        $request->validate([
            'backup_file' => ['required', 'file', 'max:10240'], // 10MB max
            'passphrase' => ['required', 'string', 'min:12'],
            'current_password' => ['required', 'current_password'],
        ]);

        try {
            $blob = file_get_contents($request->file('backup_file')->getRealPath());
            $payload = $this->backupService->decryptBackup($blob, $request->input('passphrase'));
            $stats = $this->backupService->importPayload($payload);

            $this->audit->log('imported', $request->user(), null, $stats);

            return back()->with(
                'success',
                "Imported {$stats['credentials_imported']} credentials and {$stats['categories_imported']} categories.",
            );
        } catch (\RuntimeException $e) {
            return back()->with('error', $e->getMessage());
        } catch (\Throwable $e) {
            report($e);
            return back()->with('error', 'Import failed. Check the file and passphrase.');
        }
    }
}
