<?php

use App\Http\Controllers\AuditLogController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\CredentialController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\GlobalSearchController;
use App\Http\Controllers\PasswordGeneratorController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\Auth\TwoFactorController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Public landing — redirect straight to login or dashboard.
|--------------------------------------------------------------------------
*/
Route::get('/', fn () => redirect(auth()->check() ? route('dashboard') : route('login')));

/*
|--------------------------------------------------------------------------
| Authenticated app routes.
|--------------------------------------------------------------------------
| - `auth` : password authenticated
| - `2fa.enabled` : forces 2FA setup if not yet enabled
| - `2fa.verified` : forces 2FA TOTP/recovery code per session
| - `log.activity` : writes credential-access audit entries
*/
Route::middleware(['auth'])->group(function () {

    Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

    // Credentials
    Route::resource('credentials', CredentialController::class);
    Route::post('credentials/{credential}/favorite', [CredentialController::class, 'toggleFavorite'])->name('credentials.favorite');
    Route::post('credentials/{credential}/reveal', [CredentialController::class, 'reveal'])->name('credentials.reveal');
    Route::post('credentials/{credential}/copy', [CredentialController::class, 'copyPayload'])->name('credentials.copy');
    Route::post('credentials/bulk-delete', [CredentialController::class, 'bulkDelete'])->name('credentials.bulk-delete');
    Route::post('credentials/bulk-export', [CredentialController::class, 'bulkExport'])->name('credentials.bulk-export');

    // Categories
    Route::resource('categories', CategoryController::class)->except(['show']);

    // Audit log
    Route::get('audit', [AuditLogController::class, 'index'])->name('audit.index');
    Route::get('audit/export', [AuditLogController::class, 'export'])->name('audit.export');

    // Tools
    Route::get('tools/generator', [PasswordGeneratorController::class, 'index'])->name('tools.generator');
    Route::post('tools/generator', [PasswordGeneratorController::class, 'generate'])->name('tools.generator.api');

    // Global search (Cmd+K)
    Route::get('search', [GlobalSearchController::class, 'search'])->name('search');

    // Settings
    Route::prefix('settings')->name('settings.')->group(function () {
        Route::get('/', fn () => redirect()->route('settings.profile'));
        Route::get('profile', [SettingsController::class, 'profile'])->name('profile');
        Route::patch('profile', [SettingsController::class, 'updateProfile'])->name('profile.update');

        Route::get('security', [SettingsController::class, 'security'])->name('security');
        Route::patch('security/password', [SettingsController::class, 'updatePassword'])->name('security.password');
        Route::post('security/2fa/regenerate', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('security.2fa.regenerate');

        Route::get('preferences', [SettingsController::class, 'preferences'])->name('preferences');
        Route::patch('preferences', [SettingsController::class, 'updatePreferences'])->name('preferences.update');

        Route::get('backup', [SettingsController::class, 'backup'])->name('backup');
        Route::post('backup/export', [SettingsController::class, 'exportBackup'])->name('backup.export');
        Route::post('backup/import', [SettingsController::class, 'importBackup'])->name('backup.import');
    });
});

require __DIR__.'/auth.php';
