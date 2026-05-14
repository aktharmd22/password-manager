<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\ConfirmablePasswordController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\Auth\TwoFactorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Auth routes for SecureVault
|--------------------------------------------------------------------------
| Single-user internal tool: public registration and email verification are
| intentionally NOT exposed. Password recovery is allowed (admin retains
| email access) but a recovered password still requires 2FA on next login.
*/

Route::middleware('guest')->group(function () {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store'])
        ->middleware('throttle:10,1');

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::middleware('auth')->group(function () {
    // 2FA setup (mandatory on first login when 2FA is not yet enabled)
    Route::get('two-factor/setup', [TwoFactorController::class, 'setup'])->name('two-factor.setup');
    Route::post('two-factor/setup', [TwoFactorController::class, 'setupStore'])->name('two-factor.setup.store');

    // Recovery codes (shown once after setup or regeneration)
    Route::get('two-factor/recovery-codes', [TwoFactorController::class, 'recoveryCodes'])->name('two-factor.recovery-codes');
    Route::post('two-factor/recovery-codes/acknowledge', [TwoFactorController::class, 'recoveryCodesAcknowledge'])->name('two-factor.recovery-codes.acknowledge');

    // 2FA verify (required on every login after setup)
    Route::get('two-factor/verify', [TwoFactorController::class, 'verify'])->name('two-factor.verify');
    Route::post('two-factor/verify', [TwoFactorController::class, 'verifyStore'])
        ->middleware('throttle:10,1')
        ->name('two-factor.verify.store');

    // Confirm password before sensitive operations (settings changes, etc.)
    Route::get('confirm-password', [ConfirmablePasswordController::class, 'show'])->name('password.confirm');
    Route::post('confirm-password', [ConfirmablePasswordController::class, 'store']);

    Route::put('password', [PasswordController::class, 'update'])->name('password.update');

    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');
});
