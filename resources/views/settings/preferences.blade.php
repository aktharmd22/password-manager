<x-app-layout :title="'Preferences'" :breadcrumbs="[['label' => 'Settings', 'href' => route('settings.profile')], ['label' => 'Preferences']]">
    <div class="max-w-3xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Preferences</h1>
            <p class="mt-1 text-sm text-vault-text-subtle">Local-only UI preferences. Stored in your browser, not on the server.</p>
        </div>

        @include('settings._nav')

        <x-card
            title="Display & behavior"
            description="These settings apply only on this device."
        >
            <div
                x-data="{
                    theme: localStorage.getItem('vault-theme') || 'dark',
                    defaultLength: parseInt(localStorage.getItem('vault-default-pw-length') || '{{ config('vault.default_password_length') }}'),
                    clipboardSeconds: parseInt(localStorage.getItem('vault-clipboard-seconds') || '{{ config('vault.clipboard_clear_seconds') }}'),
                    save() {
                        localStorage.setItem('vault-theme', this.theme);
                        localStorage.setItem('vault-default-pw-length', this.defaultLength.toString());
                        localStorage.setItem('vault-clipboard-seconds', this.clipboardSeconds.toString());
                        if (this.theme === 'dark') document.documentElement.classList.add('dark');
                        else document.documentElement.classList.remove('dark');
                        Alpine.store('theme').current = this.theme;
                        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Preferences saved' } }));
                    },
                }"
                class="space-y-5"
            >
                <div>
                    <label class="block text-sm font-medium mb-2">Theme</label>
                    <div class="flex items-center gap-2">
                        <button
                            type="button"
                            @click="theme = 'dark'"
                            :class="theme === 'dark' ? 'bg-vault-accent-soft text-vault-accent border-vault-accent/40' : 'border-vault-border-light dark:border-vault-border'"
                            class="flex items-center gap-2 px-4 py-2 rounded-lg border text-sm transition-all"
                        >
                            <x-icon name="moon" class="w-4 h-4" /> Dark
                        </button>
                        <button
                            type="button"
                            @click="theme = 'light'"
                            :class="theme === 'light' ? 'bg-vault-accent-soft text-vault-accent border-vault-accent/40' : 'border-vault-border-light dark:border-vault-border'"
                            class="flex items-center gap-2 px-4 py-2 rounded-lg border text-sm transition-all"
                        >
                            <x-icon name="sun" class="w-4 h-4" /> Light
                        </button>
                    </div>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-sm font-medium">Default password length</label>
                        <span class="font-mono text-sm text-vault-accent" x-text="defaultLength"></span>
                    </div>
                    <input
                        type="range"
                        x-model.number="defaultLength"
                        min="8"
                        max="64"
                        step="1"
                        class="w-full h-1.5 bg-vault-border-light dark:bg-vault-border rounded-full appearance-none cursor-pointer accent-vault-accent"
                    />
                    <p class="text-xs text-vault-text-subtle mt-1">Used by the generator and the inline "Generate" button.</p>
                </div>

                <div>
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-sm font-medium">Clipboard auto-clear (seconds)</label>
                        <span class="font-mono text-sm text-vault-accent" x-text="clipboardSeconds + 's'"></span>
                    </div>
                    <input
                        type="range"
                        x-model.number="clipboardSeconds"
                        min="5"
                        max="120"
                        step="5"
                        class="w-full h-1.5 bg-vault-border-light dark:bg-vault-border rounded-full appearance-none cursor-pointer accent-vault-accent"
                    />
                    <p class="text-xs text-vault-text-subtle mt-1">How long a copied password stays on the clipboard before being wiped.</p>
                </div>

                <div class="pt-2">
                    <x-button type="button" variant="primary" icon="save" @click="save()">Save preferences</x-button>
                </div>
            </div>
        </x-card>

        <x-card title="System defaults" description="Read-only — set in .env on the server.">
            <dl class="text-sm space-y-2">
                <div class="flex justify-between gap-2">
                    <dt class="text-vault-text-subtle">Session lifetime</dt>
                    <dd>{{ config('session.lifetime') }} minutes</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-vault-text-subtle">Idle auto-logout</dt>
                    <dd>{{ config('vault.idle_timeout_seconds') }} seconds</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-vault-text-subtle">Password reveal timeout</dt>
                    <dd>{{ config('vault.password_reveal_seconds') }} seconds</dd>
                </div>
                <div class="flex justify-between gap-2">
                    <dt class="text-vault-text-subtle">Server clipboard default</dt>
                    <dd>{{ config('vault.clipboard_clear_seconds') }} seconds</dd>
                </div>
            </dl>
        </x-card>
    </div>
</x-app-layout>
