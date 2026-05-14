<x-guest-layout :title="'Set up two-factor authentication'">

    <div class="px-8 pt-8">
        <div class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-vault-accent-soft text-vault-accent mb-4">
            <x-icon name="shield-plus" class="w-5 h-5" />
        </div>
        <h1 class="text-xl font-semibold tracking-tight">Enable two-factor authentication</h1>
        <p class="mt-1 text-sm text-vault-text-subtle">
            Scan the QR code with Google Authenticator, 1Password, Authy or any TOTP app,
            then enter the 6-digit code shown to confirm.
        </p>
    </div>

    <div class="px-8 pt-6 pb-8">
        <div class="bg-white rounded-xl p-4 flex items-center justify-center" style="width: 240px; margin: 0 auto;">
            {!! $qrSvg !!}
        </div>

        <div class="mt-4 p-3 rounded-lg bg-vault-border-light/30 dark:bg-vault-bg/40 border border-vault-border-light dark:border-vault-border">
            <p class="text-xs text-vault-text-subtle mb-1.5">Can't scan? Enter this secret manually:</p>
            <div
                class="flex items-center gap-2"
                x-data="{
                    copy() {
                        navigator.clipboard.writeText('{{ $secret }}');
                        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Secret copied' }}));
                    }
                }"
            >
                <code class="flex-1 font-mono text-sm tracking-wider break-all">{{ $secret }}</code>
                <button @click="copy" class="p-1.5 text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text" type="button">
                    <x-icon name="copy" class="w-4 h-4" />
                </button>
            </div>
        </div>

        <form method="POST" action="{{ route('two-factor.setup.store') }}" class="mt-6 space-y-4">
            @csrf

            <x-input
                name="code"
                label="6-digit verification code"
                placeholder="123 456"
                inputmode="numeric"
                autocomplete="one-time-code"
                required
                autofocus
                hint="Enter the code currently shown in your authenticator app."
            />

            <x-button type="submit" variant="primary" class="w-full" icon="check" iconPosition="right">
                Verify and continue
            </x-button>

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="w-full text-center text-xs text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text">
                    Cancel and sign out
                </button>
            </form>
        </form>
    </div>
</x-guest-layout>
