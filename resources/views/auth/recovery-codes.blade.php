<x-guest-layout :title="'Save your recovery codes'">

    <div class="p-8">
        <div class="inline-flex items-center justify-center w-10 h-10 rounded-lg bg-vault-warning/10 text-vault-warning mb-4">
            <x-icon name="key-square" class="w-5 h-5" />
        </div>
        <h1 class="text-xl font-semibold tracking-tight">Save your recovery codes</h1>
        <p class="mt-1 text-sm text-vault-text-subtle">
            If you lose access to your authenticator, these codes are the only way back in.
            <strong class="text-vault-warning">They will not be shown again.</strong>
        </p>

        <div
            x-data="{
                copy() {
                    navigator.clipboard.writeText(@js(implode(PHP_EOL, $codes)));
                    window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'All recovery codes copied' }}));
                },
                download() {
                    const blob = new Blob([@js(implode(PHP_EOL, $codes))], { type: 'text/plain' });
                    const url = URL.createObjectURL(blob);
                    const a = document.createElement('a');
                    a.href = url;
                    a.download = 'securevault-recovery-codes.txt';
                    a.click();
                    URL.revokeObjectURL(url);
                },
            }"
            class="mt-6"
        >
            <div class="p-4 rounded-xl bg-vault-border-light/30 dark:bg-vault-bg/40 border border-vault-border-light dark:border-vault-border">
                <ul class="grid grid-cols-2 gap-2 font-mono text-sm tracking-wider">
                    @foreach ($codes as $code)
                        <li class="px-2 py-1.5 rounded bg-white dark:bg-vault-surface-elevated border border-vault-border-light dark:border-vault-border">{{ $code }}</li>
                    @endforeach
                </ul>
            </div>

            <div class="mt-4 flex items-center gap-2">
                <x-button variant="secondary" type="button" @click="copy" icon="copy" class="flex-1">Copy all</x-button>
                <x-button variant="secondary" type="button" @click="download" icon="download" class="flex-1">Download .txt</x-button>
            </div>
        </div>

        <form method="POST" action="{{ route('two-factor.recovery-codes.acknowledge') }}" class="mt-6">
            @csrf
            <label class="flex items-start gap-2 cursor-pointer text-sm select-none">
                <input type="checkbox" required class="mt-1 w-4 h-4 rounded border-vault-border-light dark:border-vault-border text-vault-accent focus:ring-vault-accent/40 bg-transparent">
                <span>I have saved these recovery codes somewhere safe.</span>
            </label>
            <x-button type="submit" variant="primary" class="w-full mt-4" icon="check" iconPosition="right">
                Continue to dashboard
            </x-button>
        </form>
    </div>
</x-guest-layout>
