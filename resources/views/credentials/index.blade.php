<x-app-layout :title="'Credentials'" :breadcrumbs="[['label' => 'Credentials']]">
    <div
        x-data="credentialList()"
        x-init="init()"
        class="space-y-6"
    >
        {{-- Header --}}
        <div class="flex items-end justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Credentials</h1>
                <p class="mt-1 text-sm text-vault-text-subtle">
                    {{ $credentials->total() }} {{ Str::plural('credential', $credentials->total()) }} stored
                </p>
            </div>
            <x-button :href="route('credentials.create')" icon="plus">New credential</x-button>
        </div>

        {{-- Filter bar --}}
        <form method="GET" action="{{ route('credentials.index') }}" class="card p-4">
            <div class="flex items-center gap-2 flex-wrap">
                <div class="relative flex-1 min-w-[240px]">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-vault-text-subtle">
                        <x-icon name="search" class="w-4 h-4" />
                    </div>
                    <input
                        type="text"
                        name="q"
                        value="{{ $filters['q'] }}"
                        placeholder="Search by title, username, URL…"
                        class="input pl-9"
                    />
                </div>

                <select name="category" class="input max-w-[200px]" onchange="this.form.submit()">
                    <option value="">All categories</option>
                    @foreach ($categories as $cat)
                        <option value="{{ $cat->slug }}" {{ $filters['category'] === $cat->slug ? 'selected' : '' }}>
                            {{ $cat->name }}
                        </option>
                    @endforeach
                </select>

                <select name="sort" class="input max-w-[180px]" onchange="this.form.submit()">
                    <option value="recent" {{ $filters['sort'] === 'recent' ? 'selected' : '' }}>Recently added</option>
                    <option value="oldest" {{ $filters['sort'] === 'oldest' ? 'selected' : '' }}>Oldest first</option>
                    <option value="title" {{ $filters['sort'] === 'title' ? 'selected' : '' }}>Title (A–Z)</option>
                    <option value="updated" {{ $filters['sort'] === 'updated' ? 'selected' : '' }}>Recently updated</option>
                    <option value="accessed" {{ $filters['sort'] === 'accessed' ? 'selected' : '' }}>Recently accessed</option>
                </select>

                <label class="flex items-center gap-2 px-3 py-2 rounded-lg border border-vault-border-light dark:border-vault-border cursor-pointer hover:bg-vault-border-light/40 dark:hover:bg-vault-surface-elevated transition-all">
                    <input type="checkbox" name="favorites" value="1" {{ $filters['favorites'] ? 'checked' : '' }} class="w-4 h-4 rounded text-vault-accent focus:ring-vault-accent/40 bg-transparent border-vault-border-light dark:border-vault-border" onchange="this.form.submit()">
                    <x-icon name="star" class="w-4 h-4 text-vault-warning" />
                    <span class="text-sm">Favorites</span>
                </label>

                <x-button type="submit" variant="secondary" icon="filter">Apply</x-button>

                @if ($filters['q'] || $filters['category'] || $filters['favorites'] || $filters['tag'])
                    <a href="{{ route('credentials.index') }}" class="text-sm text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text">
                        Clear
                    </a>
                @endif
            </div>

            @if ($allTags->isNotEmpty())
                <div class="flex items-center gap-1.5 flex-wrap mt-3 pt-3 border-t border-vault-border-light dark:border-vault-border">
                    <span class="text-xs text-vault-text-subtle mr-1">Tags:</span>
                    @foreach ($allTags as $tag)
                        <a
                            href="{{ route('credentials.index', array_merge(request()->except('tag', 'page'), $filters['tag'] === $tag ? [] : ['tag' => $tag])) }}"
                            class="badge {{ $filters['tag'] === $tag ? 'bg-vault-accent-soft text-vault-accent' : 'bg-vault-border-light/60 text-vault-text-light/80 dark:bg-vault-surface-elevated dark:text-vault-text-muted hover:bg-vault-border-light dark:hover:bg-vault-border' }}"
                        >
                            #{{ $tag }}
                        </a>
                    @endforeach
                </div>
            @endif
        </form>

        {{-- Bulk action bar --}}
        <div
            x-show="selected.length > 0"
            x-transition
            class="card p-3 flex items-center gap-3 sticky top-20 z-10 bg-vault-accent-soft border-vault-accent/40"
            x-cloak
        >
            <span class="text-sm font-medium text-vault-accent" x-text="`${selected.length} selected`"></span>
            <div class="flex-1"></div>
            <button @click="bulkExport()" class="btn btn-secondary btn-sm px-3 py-1.5 text-sm">
                <x-icon name="download" class="w-4 h-4" /> Export CSV
            </button>
            <button @click="bulkDelete()" class="btn btn-danger px-3 py-1.5 text-sm">
                <x-icon name="trash-2" class="w-4 h-4" /> Delete
            </button>
            <button @click="selected = []" class="text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text text-sm">
                Cancel
            </button>
        </div>

        {{-- Table --}}
        <x-card padding="none">
            @if ($credentials->isEmpty())
                <x-empty-state
                    icon="key-round"
                    title="No credentials match your filters"
                    description="Try adjusting your search or clear filters to see everything."
                >
                    <x-button :href="route('credentials.create')" icon="plus">Add your first credential</x-button>
                </x-empty-state>
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase tracking-wide text-vault-text-subtle border-b border-vault-border-light dark:border-vault-border">
                            <tr>
                                <th class="px-4 py-3 w-10">
                                    <input
                                        type="checkbox"
                                        @change="toggleAll($event.target.checked)"
                                        :checked="allSelected"
                                        class="w-4 h-4 rounded text-vault-accent focus:ring-vault-accent/40 bg-transparent border-vault-border-light dark:border-vault-border"
                                    >
                                </th>
                                <th class="px-2 py-3 w-10"></th>
                                <th class="px-3 py-3 text-left font-medium">Title</th>
                                <th class="px-3 py-3 text-left font-medium hidden md:table-cell">Category</th>
                                <th class="px-3 py-3 text-left font-medium hidden lg:table-cell">Username</th>
                                <th class="px-3 py-3 text-left font-medium hidden xl:table-cell">Updated</th>
                                <th class="px-3 py-3 text-right font-medium">Actions</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-vault-border-light dark:divide-vault-border">
                            @foreach ($credentials as $cred)
                                <tr
                                    class="hover:bg-vault-border-light/30 dark:hover:bg-vault-surface-elevated/50 transition-colors"
                                    :class="selected.includes({{ $cred->id }}) ? 'bg-vault-accent-soft' : ''"
                                >
                                    <td class="px-4 py-3">
                                        <input
                                            type="checkbox"
                                            value="{{ $cred->id }}"
                                            @change="toggle({{ $cred->id }})"
                                            :checked="selected.includes({{ $cred->id }})"
                                            class="w-4 h-4 rounded text-vault-accent focus:ring-vault-accent/40 bg-transparent border-vault-border-light dark:border-vault-border"
                                        >
                                    </td>
                                    <td class="px-2 py-3">
                                        <button
                                            type="button"
                                            x-data="{ fav: @js((bool) $cred->is_favorite) }"
                                            @click="
                                                fav = !fav;
                                                fetch('{{ route('credentials.favorite', $cred) }}', {
                                                    method: 'POST',
                                                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                                });
                                            "
                                            :class="fav ? 'text-vault-warning' : 'text-vault-text-subtle/40 hover:text-vault-warning'"
                                            class="transition-colors"
                                            title="Toggle favorite"
                                        >
                                            <x-icon name="star" class="w-4 h-4" />
                                        </button>
                                    </td>
                                    <td class="px-3 py-3">
                                        <a href="{{ route('credentials.show', $cred) }}" class="flex items-center gap-2 group">
                                            <div
                                                class="w-7 h-7 rounded-md flex items-center justify-center shrink-0"
                                                style="background-color: {{ $cred->category?->color }}1A; color: {{ $cred->category?->color }};"
                                            >
                                                <x-icon :name="$cred->category?->icon ?? 'key-round'" class="w-3.5 h-3.5" />
                                            </div>
                                            <div class="min-w-0">
                                                <p class="font-medium truncate group-hover:text-vault-accent transition-colors">{{ $cred->title }}</p>
                                                @if ($cred->url)
                                                    <p class="text-xs text-vault-text-subtle truncate">{{ parse_url($cred->url, PHP_URL_HOST) ?? $cred->url }}</p>
                                                @endif
                                            </div>
                                        </a>
                                    </td>
                                    <td class="px-3 py-3 hidden md:table-cell">
                                        <x-badge>{{ $cred->category?->name }}</x-badge>
                                    </td>
                                    <td class="px-3 py-3 hidden lg:table-cell text-vault-text-subtle">
                                        {{ $cred->username ?: $cred->email ?: '—' }}
                                    </td>
                                    <td class="px-3 py-3 hidden xl:table-cell text-vault-text-subtle text-xs">
                                        {{ $cred->updated_at->diffForHumans() }}
                                    </td>
                                    <td class="px-3 py-3 text-right">
                                        <div class="flex items-center justify-end gap-0.5">
                                            <button
                                                type="button"
                                                @click="copyField({{ $cred->id }}, 'username', '{{ addslashes($cred->title) }}')"
                                                class="p-1.5 rounded hover:bg-vault-border-light dark:hover:bg-vault-surface text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text"
                                                title="Copy username"
                                                @if (!$cred->username && !$cred->email) disabled @endif
                                            >
                                                <x-icon name="user" class="w-4 h-4" />
                                            </button>
                                            <button
                                                type="button"
                                                @click="copyField({{ $cred->id }}, 'password', '{{ addslashes($cred->title) }}')"
                                                class="p-1.5 rounded hover:bg-vault-border-light dark:hover:bg-vault-surface text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text"
                                                title="Copy password (clears after {{ config('vault.clipboard_clear_seconds') }}s)"
                                            >
                                                <x-icon name="copy" class="w-4 h-4" />
                                            </button>
                                            <a href="{{ route('credentials.show', $cred) }}" class="p-1.5 rounded hover:bg-vault-border-light dark:hover:bg-vault-surface text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text" title="View">
                                                <x-icon name="eye" class="w-4 h-4" />
                                            </a>
                                            <a href="{{ route('credentials.edit', $cred) }}" class="p-1.5 rounded hover:bg-vault-border-light dark:hover:bg-vault-surface text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text" title="Edit">
                                                <x-icon name="pencil" class="w-4 h-4" />
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t border-vault-border-light dark:border-vault-border">
                    {{ $credentials->links() }}
                </div>
            @endif
        </x-card>

        {{-- Hidden forms for bulk actions --}}
        <form id="bulk-delete-form" method="POST" action="{{ route('credentials.bulk-delete') }}" class="hidden">
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
        </form>
        <form id="bulk-export-form" method="POST" action="{{ route('credentials.bulk-export') }}" class="hidden">
            @csrf
            <template x-for="id in selected" :key="id">
                <input type="hidden" name="ids[]" :value="id">
            </template>
        </form>
    </div>

    <script>
        function credentialList() {
                return {
                    selected: [],
                    pageIds: @js($credentials->pluck('id')->all()),

                    get allSelected() {
                        return this.pageIds.length > 0 && this.pageIds.every(id => this.selected.includes(id));
                    },

                    init() {},

                    toggle(id) {
                        if (this.selected.includes(id)) {
                            this.selected = this.selected.filter(x => x !== id);
                        } else {
                            this.selected.push(id);
                        }
                    },

                    toggleAll(check) {
                        if (check) {
                            this.selected = [...new Set([...this.selected, ...this.pageIds])];
                        } else {
                            this.selected = this.selected.filter(id => !this.pageIds.includes(id));
                        }
                    },

                    async copyField(id, field, title) {
                        const res = await fetch(`/credentials/${id}/copy`, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-CSRF-TOKEN': '{{ csrf_token() }}',
                                'Accept': 'application/json',
                            },
                            body: JSON.stringify({ field }),
                        });
                        if (!res.ok) {
                            window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Copy failed' } }));
                            return;
                        }
                        const data = await res.json();
                        window.copyToClipboard(data.value, {
                            clearAfter: data.clear_after,
                            label: field === 'password' ? `${title} password` : `${title} ${field}`,
                        });
                    },

                    async bulkDelete() {
                        if (!confirm(`Delete ${this.selected.length} credential${this.selected.length === 1 ? '' : 's'}? This is reversible from the trash.`)) return;
                        document.getElementById('bulk-delete-form').submit();
                    },

                    bulkExport() {
                        if (!confirm(`Export ${this.selected.length} credential${this.selected.length === 1 ? '' : 's'} to CSV? The file will contain plaintext passwords.`)) return;
                        document.getElementById('bulk-export-form').submit();
                    },
                };
        }
    </script>
</x-app-layout>
