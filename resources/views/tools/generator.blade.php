<x-app-layout :title="'Password generator'" :breadcrumbs="[['label' => 'Generator']]">
    <div
        x-data="generatorTool()"
        x-init="generate()"
        class="max-w-3xl space-y-6"
    >
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Password generator</h1>
            <p class="mt-1 text-sm text-vault-text-subtle">Server-side CSPRNG. Generated passwords stay in your browser only.</p>
        </div>

        {{-- Output --}}
        <x-card>
            <div class="space-y-3">
                <div class="relative">
                    <input
                        type="text"
                        x-model="password"
                        readonly
                        class="input font-mono text-base pr-24 select-all"
                        @click="$event.target.select()"
                    />
                    <div class="absolute right-2 top-1/2 -translate-y-1/2 flex items-center gap-1">
                        <button
                            type="button"
                            @click="generate()"
                            class="p-2 rounded hover:bg-vault-border-light dark:hover:bg-vault-surface text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text"
                            title="Regenerate"
                        >
                            <x-icon name="refresh-cw" class="w-4 h-4" />
                        </button>
                        <button
                            type="button"
                            @click="copy()"
                            class="p-2 rounded hover:bg-vault-border-light dark:hover:bg-vault-surface text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text"
                            title="Copy"
                        >
                            <x-icon name="copy" class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <div class="flex items-center gap-2" x-show="password.length > 0">
                    <div class="flex-1 h-1 rounded-full bg-vault-border-light dark:bg-vault-border overflow-hidden">
                        <div class="h-full transition-all duration-300" :class="strengthColor" :style="`width: ${(score + 1) * 20}%`"></div>
                    </div>
                    <span class="text-xs font-medium" :class="strengthTextColor" x-text="strengthLabel"></span>
                </div>
            </div>
        </x-card>

        {{-- Controls --}}
        <x-card title="Options">
            <div class="space-y-5">
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-sm font-medium">Length</label>
                        <span class="font-mono text-sm text-vault-accent" x-text="length"></span>
                    </div>
                    <input
                        type="range"
                        x-model.number="length"
                        @input.debounce.100ms="generate()"
                        min="8"
                        max="64"
                        step="1"
                        class="w-full h-1.5 bg-vault-border-light dark:bg-vault-border rounded-full appearance-none cursor-pointer accent-vault-accent"
                    />
                    <div class="flex justify-between text-xs text-vault-text-subtle mt-1">
                        <span>8</span><span>16</span><span>32</span><span>48</span><span>64</span>
                    </div>
                </div>

                <div class="grid grid-cols-2 gap-3">
                    <template x-for="opt in [
                        { key: 'uppercase', label: 'Uppercase (A-Z)' },
                        { key: 'lowercase', label: 'Lowercase (a-z)' },
                        { key: 'numbers', label: 'Numbers (0-9)' },
                        { key: 'symbols', label: 'Symbols (!@#…)' },
                        { key: 'exclude_similar', label: 'Exclude similar (0, O, l, 1, I)' },
                    ]" :key="opt.key">
                        <label class="flex items-center gap-2.5 cursor-pointer p-2 rounded-lg hover:bg-vault-border-light/30 dark:hover:bg-vault-surface-elevated/40 transition-all">
                            <input
                                type="checkbox"
                                :checked="options[opt.key]"
                                @change="options[opt.key] = $event.target.checked; generate();"
                                class="w-4 h-4 rounded text-vault-accent focus:ring-vault-accent/40 bg-transparent border-vault-border-light dark:border-vault-border"
                            >
                            <span class="text-sm" x-text="opt.label"></span>
                        </label>
                    </template>
                </div>
            </div>
        </x-card>

        {{-- History --}}
        <x-card title="History" description="Last 5 passwords generated this session. Never stored on the server.">
            <template x-if="history.length === 0">
                <p class="text-sm text-vault-text-subtle py-2">Nothing here yet — generate to populate.</p>
            </template>
            <ul class="space-y-1" x-show="history.length > 0">
                <template x-for="(item, idx) in history" :key="idx">
                    <li class="flex items-center gap-2 py-1.5 px-2 rounded hover:bg-vault-border-light/30 dark:hover:bg-vault-surface-elevated/40 group">
                        <code class="flex-1 font-mono text-xs truncate" x-text="item"></code>
                        <button
                            type="button"
                            @click="navigator.clipboard.writeText(item); window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Copied' }}));"
                            class="opacity-0 group-hover:opacity-100 p-1 rounded hover:bg-vault-border-light dark:hover:bg-vault-surface text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text transition-opacity"
                        >
                            <x-icon name="copy" class="w-3.5 h-3.5" />
                        </button>
                    </li>
                </template>
            </ul>
        </x-card>

        <script>
            function generatorTool() {
                return {
                    password: '',
                    length: {{ config('vault.default_password_length') }},
                    options: {
                        uppercase: true,
                        lowercase: true,
                        numbers: true,
                        symbols: true,
                        exclude_similar: true,
                    },
                    score: 0,
                    strengthLabel: '',
                    history: [],

                    get strengthColor() {
                        return ['bg-vault-danger', 'bg-vault-danger', 'bg-vault-warning', 'bg-vault-success', 'bg-vault-success'][this.score];
                    },
                    get strengthTextColor() {
                        return ['text-vault-danger', 'text-vault-danger', 'text-vault-warning', 'text-vault-success', 'text-vault-success'][this.score];
                    },

                    async generate() {
                        try {
                            const res = await fetch('{{ route('tools.generator.api') }}', {
                                method: 'POST',
                                headers: {
                                    'Content-Type': 'application/json',
                                    'Accept': 'application/json',
                                    'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                },
                                body: JSON.stringify({ length: this.length, ...this.options }),
                            });
                            if (!res.ok) throw new Error('failed');
                            const data = await res.json();
                            if (this.password) this.history.unshift(this.password);
                            this.history = this.history.slice(0, 5);
                            this.password = data.password;
                            this.score = data.strength.score;
                            this.strengthLabel = data.strength.label;
                        } catch (_) {
                            window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Generation failed' } }));
                        }
                    },

                    async copy() {
                        await navigator.clipboard.writeText(this.password);
                        window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Copied to clipboard' } }));
                    },
                };
            }
        </script>
    </div>
</x-app-layout>
