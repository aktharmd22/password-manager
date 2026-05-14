<x-guest-layout :title="'Verify two-factor authentication'">

    <div x-data="{ mode: 'totp' }" class="p-8">
        <div class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-vault-accent-soft text-vault-accent mb-4">
            <x-icon name="shield-check" class="w-5 h-5" />
        </div>
        <h1 class="text-xl font-semibold tracking-tight">Two-factor verification</h1>
        <p class="mt-1 text-sm text-vault-text-subtle">
            <template x-if="mode === 'totp'">
                <span>Enter the 6-digit code from your authenticator app.</span>
            </template>
            <template x-if="mode === 'recovery'">
                <span>Enter one of your saved recovery codes. Each code works only once.</span>
            </template>
        </p>

        <form method="POST" action="{{ route('two-factor.verify.store') }}" class="mt-6 space-y-4">
            @csrf

            <div x-show="mode === 'totp'">
                <x-input
                    name="code"
                    label="6-digit code"
                    placeholder="123 456"
                    inputmode="numeric"
                    autocomplete="one-time-code"
                    autofocus
                />
            </div>

            <div x-show="mode === 'recovery'" x-cloak>
                <x-input
                    name="recovery_code"
                    label="Recovery code"
                    placeholder="xxxxx-xxxxx"
                    autocomplete="off"
                />
            </div>

            <x-button type="submit" variant="primary" class="w-full" icon="log-in" iconPosition="right">
                Verify and continue
            </x-button>

            <div class="flex items-center justify-between text-xs">
                <button
                    type="button"
                    @click="mode = mode === 'totp' ? 'recovery' : 'totp'"
                    class="text-vault-accent hover:text-vault-accent-hover"
                >
                    <span x-show="mode === 'totp'">Use a recovery code instead</span>
                    <span x-show="mode === 'recovery'" x-cloak>Use authenticator app instead</span>
                </button>

                <form method="POST" action="{{ route('logout') }}">
                    @csrf
                    <button type="submit" class="text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text">Sign out</button>
                </form>
            </div>
        </form>
    </div>
</x-guest-layout>
