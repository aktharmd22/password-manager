{{-- Global search palette stub. Replaced with full Cmd+K implementation in the
     "global features" module. Visible only via window event 'open-search'. --}}
<div
    x-data="{ open: false, query: '', results: [], loading: false, async fetchResults() {
        if (this.query.length < 1) { this.results = []; return; }
        this.loading = true;
        try {
            const res = await fetch(`{{ route('search') }}?q=` + encodeURIComponent(this.query), {
                headers: { 'Accept': 'application/json' },
            });
            this.results = await res.json();
        } finally { this.loading = false; }
    }}"
    x-on:open-search.window="open = true; $nextTick(() => $refs.input.focus())"
    x-on:keydown.window.prevent.cmd.k="open = true; $nextTick(() => $refs.input.focus())"
    x-on:keydown.window.prevent.ctrl.k="open = true; $nextTick(() => $refs.input.focus())"
    x-on:keydown.escape.window="open = false"
    x-show="open"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
    x-cloak
>
    <div class="flex items-start justify-center min-h-screen px-4 pt-24 pb-6">
        <div x-show="open" x-transition.opacity class="fixed inset-0 glass" @click="open = false"></div>

        <div
            x-show="open"
            x-transition:enter="transition ease-out duration-150"
            x-transition:enter-start="opacity-0 -translate-y-2 scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 scale-100"
            class="relative w-full max-w-2xl card shadow-vault-lg overflow-hidden"
        >
            <div class="flex items-center gap-3 px-4 py-3 border-b border-vault-border-light dark:border-vault-border">
                <x-icon name="search" class="w-5 h-5 text-vault-text-subtle" />
                <input
                    x-ref="input"
                    x-model="query"
                    @input.debounce.200ms="fetchResults()"
                    type="text"
                    placeholder="Search credentials by title, username, URL, tag…"
                    class="flex-1 bg-transparent border-none outline-none text-sm placeholder:text-vault-text-subtle focus:ring-0 p-0"
                />
                <kbd class="px-1.5 py-0.5 rounded text-[10px] font-mono bg-vault-border-light/80 dark:bg-vault-bg border border-vault-border-light dark:border-vault-border">ESC</kbd>
            </div>

            <div class="max-h-[60vh] overflow-y-auto">
                <template x-if="loading">
                    <div class="px-4 py-6 text-sm text-vault-text-subtle text-center">Searching…</div>
                </template>
                <template x-if="!loading && query.length > 0 && results.length === 0">
                    <div class="px-4 py-10 text-sm text-vault-text-subtle text-center">
                        No matches for "<span x-text="query"></span>"
                    </div>
                </template>
                <template x-if="!loading && query.length === 0">
                    <div class="px-4 py-10 text-sm text-vault-text-subtle text-center">
                        Type to search across all credentials
                    </div>
                </template>
                <template x-for="item in results" :key="item.id">
                    <a
                        :href="item.url"
                        class="flex items-start gap-3 px-4 py-3 hover:bg-vault-border-light/40 dark:hover:bg-vault-surface-elevated transition-all"
                    >
                        <div class="w-8 h-8 rounded-lg flex items-center justify-center shrink-0" :style="`background-color: ${item.category_color}1A; color: ${item.category_color}`">
                            <i :data-lucide="item.category_icon" class="w-4 h-4" x-init="$nextTick(() => window.lucide?.createIcons())"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <p class="text-sm font-medium truncate" x-text="item.title"></p>
                            <p class="text-xs text-vault-text-subtle truncate" x-text="item.subtitle"></p>
                        </div>
                        <span class="text-xs text-vault-text-subtle shrink-0" x-text="item.category"></span>
                    </a>
                </template>
            </div>
        </div>
    </div>
</div>
