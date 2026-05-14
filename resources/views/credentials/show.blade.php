<x-app-layout
    :title="$credential->title"
    :breadcrumbs="[
        ['label' => 'Credentials', 'href' => route('credentials.index')],
        ['label' => $credential->title],
    ]"
>
    <div
        x-data="credentialDetail()"
        class="space-y-6"
    >
        {{-- Header --}}
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div class="flex items-start gap-4 min-w-0">
                <div
                    class="w-12 h-12 rounded-xl flex items-center justify-center shrink-0"
                    style="background-color: {{ $credential->category?->color }}1A; color: {{ $credential->category?->color }};"
                >
                    <x-icon :name="$credential->category?->icon ?? 'key-round'" class="w-6 h-6" />
                </div>
                <div class="min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-2xl font-semibold tracking-tight truncate">{{ $credential->title }}</h1>
                        <button
                            type="button"
                            x-data="{ fav: @js((bool) $credential->is_favorite) }"
                            @click="
                                fav = !fav;
                                fetch('{{ route('credentials.favorite', $credential) }}', {
                                    method: 'POST',
                                    headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                                });
                            "
                            :class="fav ? 'text-vault-warning' : 'text-vault-text-subtle/40 hover:text-vault-warning'"
                            class="transition-colors"
                        >
                            <x-icon name="star" class="w-5 h-5" />
                        </button>
                    </div>
                    <div class="flex items-center gap-2 mt-1 flex-wrap">
                        <x-badge :icon="$credential->category?->icon">{{ $credential->category?->name }}</x-badge>
                        @foreach ($credential->tags ?? [] as $tag)
                            <span class="badge bg-vault-border-light/60 dark:bg-vault-surface-elevated text-vault-text-subtle">#{{ $tag }}</span>
                        @endforeach
                        @if ($credential->password_changed_at)
                            <span class="text-xs text-vault-text-subtle">Password changed {{ $credential->password_changed_at->diffForHumans() }}</span>
                        @endif
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-2">
                <x-button variant="secondary" icon="pencil" :href="route('credentials.edit', $credential)">Edit</x-button>
                <form method="POST" action="{{ route('credentials.destroy', $credential) }}" onsubmit="return confirm('Move this credential to trash?');">
                    @csrf
                    @method('DELETE')
                    <x-button variant="danger" type="submit" icon="trash-2">Delete</x-button>
                </form>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Main detail --}}
            <div class="lg:col-span-2 space-y-6">
                <x-card title="Credentials">
                    <div class="space-y-4">
                        {{-- Username --}}
                        @if ($credential->username)
                            <x-credentials.field-row
                                label="Username"
                                icon="user"
                                :value="$credential->username"
                                copy-field="username"
                                :credential-id="$credential->id"
                            />
                        @endif

                        {{-- Email --}}
                        @if ($credential->email)
                            <x-credentials.field-row
                                label="Email"
                                icon="mail"
                                :value="$credential->email"
                                copy-field="email"
                                :credential-id="$credential->id"
                            />
                        @endif

                        {{-- Password (with reveal) --}}
                        <div class="flex items-center gap-3 py-2 group">
                            <div class="w-8 h-8 rounded-lg bg-vault-border-light/50 dark:bg-vault-bg/40 flex items-center justify-center text-vault-text-subtle shrink-0">
                                <x-icon name="lock" class="w-4 h-4" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs text-vault-text-subtle">Password</p>
                                <div class="flex items-center gap-2">
                                    <code class="font-mono text-sm truncate" x-text="passwordVisible ? plaintextPassword : maskedPassword"></code>
                                    <span x-show="revealCountdown > 0" class="text-xs text-vault-warning font-medium" x-cloak>
                                        <span x-text="revealCountdown"></span>s
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-1">
                                <button
                                    type="button"
                                    @click="reveal()"
                                    class="p-2 rounded hover:bg-vault-border-light dark:hover:bg-vault-surface text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text"
                                    :title="passwordVisible ? 'Hide' : 'Reveal for ' + {{ config('vault.password_reveal_seconds') }} + 's'"
                                >
                                    <span x-show="!passwordVisible"><x-icon name="eye" class="w-4 h-4" /></span>
                                    <span x-show="passwordVisible" x-cloak><x-icon name="eye-off" class="w-4 h-4" /></span>
                                </button>
                                <button
                                    type="button"
                                    @click="copy('password')"
                                    class="p-2 rounded hover:bg-vault-border-light dark:hover:bg-vault-surface text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text"
                                    title="Copy (clears after {{ config('vault.clipboard_clear_seconds') }}s)"
                                >
                                    <x-icon name="copy" class="w-4 h-4" />
                                </button>
                            </div>
                        </div>

                        {{-- URL --}}
                        @if ($credential->url)
                            <div class="flex items-center gap-3 py-2">
                                <div class="w-8 h-8 rounded-lg bg-vault-border-light/50 dark:bg-vault-bg/40 flex items-center justify-center text-vault-text-subtle shrink-0">
                                    <x-icon name="link" class="w-4 h-4" />
                                </div>
                                <div class="flex-1 min-w-0">
                                    <p class="text-xs text-vault-text-subtle">URL</p>
                                    <a href="{{ $credential->url }}" target="_blank" rel="noopener noreferrer" class="text-sm text-vault-accent hover:text-vault-accent-hover truncate inline-flex items-center gap-1">
                                        {{ $credential->url }}
                                        <x-icon name="external-link" class="w-3 h-3" />
                                    </a>
                                </div>
                                <button
                                    type="button"
                                    @click="copy('url')"
                                    class="p-2 rounded hover:bg-vault-border-light dark:hover:bg-vault-surface text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text"
                                >
                                    <x-icon name="copy" class="w-4 h-4" />
                                </button>
                            </div>
                        @endif
                    </div>
                </x-card>

                @if ($notes)
                    <x-card title="Notes">
                        <pre class="text-sm whitespace-pre-wrap font-sans text-vault-text-light/90 dark:text-vault-text/90">{{ $notes }}</pre>
                    </x-card>
                @endif

                @if (! empty($customFields))
                    <x-card title="Custom fields">
                        <dl class="divide-y divide-vault-border-light dark:divide-vault-border">
                            @foreach ($customFields as $field)
                                <div class="py-2.5 flex items-start gap-3">
                                    <dt class="w-32 text-xs uppercase tracking-wide text-vault-text-subtle shrink-0 pt-0.5">{{ $field['key'] }}</dt>
                                    <dd class="flex-1 min-w-0 font-mono text-sm break-all">{{ $field['value'] }}</dd>
                                    <button
                                        type="button"
                                        onclick="window.copyToClipboard(this.dataset.value, { clearAfter: {{ config('vault.clipboard_clear_seconds') }}, label: this.dataset.label })"
                                        data-value="{{ $field['value'] }}"
                                        data-label="{{ $field['key'] }}"
                                        class="p-1.5 rounded hover:bg-vault-border-light dark:hover:bg-vault-surface text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text shrink-0"
                                    >
                                        <x-icon name="copy" class="w-4 h-4" />
                                    </button>
                                </div>
                            @endforeach
                        </dl>
                    </x-card>
                @endif
            </div>

            {{-- Sidebar: history + audit --}}
            <div class="space-y-6">
                <x-card title="Metadata">
                    <dl class="text-sm space-y-2.5">
                        <div class="flex justify-between gap-2">
                            <dt class="text-vault-text-subtle">Created</dt>
                            <dd>{{ $credential->created_at->format('M j, Y · g:i A') }}</dd>
                        </div>
                        <div class="flex justify-between gap-2">
                            <dt class="text-vault-text-subtle">Last updated</dt>
                            <dd>{{ $credential->updated_at->diffForHumans() }}</dd>
                        </div>
                        @if ($credential->last_accessed_at)
                            <div class="flex justify-between gap-2">
                                <dt class="text-vault-text-subtle">Last accessed</dt>
                                <dd>{{ $credential->last_accessed_at->diffForHumans() }}</dd>
                            </div>
                        @endif
                        @if ($credential->password_changed_at)
                            <div class="flex justify-between gap-2">
                                <dt class="text-vault-text-subtle">Password age</dt>
                                <dd>{{ $credential->password_changed_at->diffForHumans(null, true) }}</dd>
                            </div>
                        @endif
                    </dl>
                </x-card>

                @if ($credential->passwordHistories->isNotEmpty())
                    <x-card title="Password history" description="Previous passwords are stored encrypted, never displayed.">
                        <ul class="space-y-2 text-sm">
                            @foreach ($credential->passwordHistories as $history)
                                <li class="flex items-center gap-2 text-vault-text-subtle">
                                    <x-icon name="rotate-ccw" class="w-3.5 h-3.5" />
                                    <span>Changed {{ $history->changed_at->diffForHumans() }}</span>
                                </li>
                            @endforeach
                        </ul>
                    </x-card>
                @endif

                <x-card title="Activity">
                    @forelse ($auditTrail as $log)
                        <div class="flex items-start gap-2.5 py-2 border-b last:border-0 border-vault-border-light dark:border-vault-border">
                            <div class="w-1.5 h-1.5 rounded-full bg-vault-accent/60 mt-2 shrink-0"></div>
                            <div class="flex-1 min-w-0 text-xs">
                                <p class="font-medium">{{ $log->actionLabel }}</p>
                                <p class="text-vault-text-subtle mt-0.5">
                                    {{ $log->created_at->diffForHumans() }}
                                    @if ($log->ip_address) · {{ $log->ip_address }} @endif
                                </p>
                            </div>
                        </div>
                    @empty
                        <p class="text-sm text-vault-text-subtle">No activity recorded yet.</p>
                    @endforelse
                </x-card>
            </div>
        </div>

        <script>
            function credentialDetail() {
                return {
                    plaintextPassword: '',
                    maskedPassword: '••••••••••••••••',
                    passwordVisible: false,
                    revealCountdown: 0,
                    revealTimer: null,

                    async reveal() {
                        if (this.passwordVisible) {
                            this.passwordVisible = false;
                            clearInterval(this.revealTimer);
                            this.revealCountdown = 0;
                            return;
                        }

                        const res = await fetch('{{ route('credentials.reveal', $credential) }}', {
                            method: 'POST',
                            headers: { 'X-CSRF-TOKEN': '{{ csrf_token() }}', 'Accept': 'application/json' },
                        });
                        if (!res.ok) {
                            window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'error', message: 'Reveal failed' } }));
                            return;
                        }
                        const data = await res.json();
                        this.plaintextPassword = data.password;
                        this.passwordVisible = true;
                        this.revealCountdown = data.reveal_seconds;

                        this.revealTimer = setInterval(() => {
                            this.revealCountdown -= 1;
                            if (this.revealCountdown <= 0) {
                                this.passwordVisible = false;
                                this.plaintextPassword = '';
                                clearInterval(this.revealTimer);
                            }
                        }, 1000);
                    },

                    async copy(field) {
                        const res = await fetch('/credentials/{{ $credential->id }}/copy', {
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
                            label: '{{ addslashes($credential->title) }} ' + field,
                        });
                    },
                };
            }
        </script>
    </div>
</x-app-layout>
