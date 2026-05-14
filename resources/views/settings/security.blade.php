<x-app-layout :title="'Security'" :breadcrumbs="[['label' => 'Settings', 'href' => route('settings.profile')], ['label' => 'Security']]">
    <div class="max-w-3xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Security</h1>
            <p class="mt-1 text-sm text-vault-text-subtle">Master password, two-factor authentication, recovery codes.</p>
        </div>

        @include('settings._nav')

        <x-card title="Two-factor authentication" description="Enabled — your account is protected by a TOTP authenticator app.">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-xl bg-vault-success/10 text-vault-success flex items-center justify-center">
                    <x-icon name="shield-check" class="w-5 h-5" />
                </div>
                <div class="flex-1">
                    <p class="text-sm font-medium">Active since {{ $user->two_factor_confirmed_at?->format('M j, Y') }}</p>
                    <p class="text-xs text-vault-text-subtle">{{ count($user->two_factor_recovery_codes ?? []) }} recovery codes remaining.</p>
                </div>
                <form method="POST" action="{{ route('settings.security.2fa.regenerate') }}" onsubmit="return confirm('Generate new recovery codes? Your old codes will stop working.');">
                    @csrf
                    <x-button type="submit" variant="secondary" icon="refresh-cw">Regenerate codes</x-button>
                </form>
            </div>
        </x-card>

        <x-card title="Change master password" description="You'll need your current password and a 2FA code.">
            <form method="POST" action="{{ route('settings.security.password') }}" class="space-y-4 max-w-md">
                @csrf
                @method('PATCH')

                <x-input name="current_password" type="password" label="Current password" autocomplete="current-password" required />

                <x-password-field name="password" label="New password" required show-generator show-strength />

                <x-input name="password_confirmation" type="password" label="Confirm new password" autocomplete="new-password" required />

                <x-input name="two_factor_code" label="2FA code" placeholder="123 456" inputmode="numeric" autocomplete="one-time-code" required />

                <div class="pt-2">
                    <x-button type="submit" variant="primary" icon="lock">Change password</x-button>
                </div>

                <p class="text-xs text-vault-text-subtle pt-2 border-t border-vault-border-light dark:border-vault-border">
                    Policy: at least {{ config('vault.password_policy.min_length') }} chars, mixed case, numbers, and symbols. Must not be on known breach lists.
                </p>
            </form>
        </x-card>

        @if ($recoveryCodesToShow)
            <x-card title="New recovery codes" description="Save these — they will not be shown again.">
                <ul class="grid grid-cols-2 gap-2 font-mono text-sm tracking-wider">
                    @foreach ($recoveryCodesToShow as $code)
                        <li class="px-2 py-1.5 rounded bg-white dark:bg-vault-surface-elevated border border-vault-border-light dark:border-vault-border">{{ $code }}</li>
                    @endforeach
                </ul>
                <form method="POST" action="{{ route('two-factor.recovery-codes.acknowledge') }}" class="mt-4">
                    @csrf
                    <x-button type="submit" variant="primary" icon="check">I have saved these codes</x-button>
                </form>
            </x-card>
        @endif
    </div>
</x-app-layout>
