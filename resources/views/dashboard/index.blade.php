<x-app-layout :title="'Dashboard'" :breadcrumbs="[['label' => 'Dashboard']]">

    <div class="space-y-8">
        {{-- Header --}}
        <div class="flex items-end justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Hello, {{ explode(' ', auth()->user()->name)[0] }}</h1>
                <p class="mt-1 text-sm text-vault-text-subtle">
                    Last signed in {{ optional(auth()->user()->last_login_at)->diffForHumans() ?? 'now' }}
                    @if (auth()->user()->last_login_ip)
                        · IP {{ auth()->user()->last_login_ip }}
                    @endif
                </p>
            </div>
            <div class="flex items-center gap-2">
                <x-button variant="secondary" :href="route('tools.generator')" icon="wand-2">Generator</x-button>
                <x-button :href="route('credentials.create')" icon="plus">New credential</x-button>
            </div>
        </div>

        {{-- Stat cards --}}
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <x-stat-card label="Total credentials" :value="$totalCredentials" icon="key-round" icon-color="#6366F1" :href="route('credentials.index')" />
            <x-stat-card label="Favorites" :value="$favoritesCount" icon="star" icon-color="#F59E0B" />
            <x-stat-card label="Categories" :value="$byCategory->count()" icon="folder-tree" icon-color="#10B981" :href="route('categories.index')" />
            <x-stat-card
                label="Last login"
                :value="optional(auth()->user()->last_login_at)->diffForHumans() ?? '—'"
                icon="clock"
                icon-color="#06B6D4"
            />
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            {{-- Recently added --}}
            <div class="lg:col-span-2 space-y-6">
                <x-card title="Recently added" description="Newest credentials added to the vault">
                    @forelse ($recentlyAdded as $cred)
                        <a href="{{ route('credentials.show', $cred) }}" class="flex items-center gap-3 py-3 first:pt-0 last:pb-0 border-b last:border-0 border-vault-border-light dark:border-vault-border group">
                            <div
                                class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                                style="background-color: {{ $cred->category?->color }}1A; color: {{ $cred->category?->color }};"
                            >
                                <x-icon :name="$cred->category?->icon ?? 'key-round'" class="w-4 h-4" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate group-hover:text-vault-accent transition-colors">{{ $cred->title }}</p>
                                <p class="text-xs text-vault-text-subtle truncate">{{ $cred->username ?: $cred->email ?: '—' }}</p>
                            </div>
                            <span class="text-xs text-vault-text-subtle shrink-0">{{ $cred->created_at->diffForHumans() }}</span>
                        </a>
                    @empty
                        <x-empty-state icon="key-round" title="No credentials yet" description="Add your first credential to get started.">
                            <x-button :href="route('credentials.create')" icon="plus">Add credential</x-button>
                        </x-empty-state>
                    @endforelse
                </x-card>

                <x-card title="Recently accessed">
                    @forelse ($recentlyAccessed as $cred)
                        <a href="{{ route('credentials.show', $cred) }}" class="flex items-center gap-3 py-3 first:pt-0 last:pb-0 border-b last:border-0 border-vault-border-light dark:border-vault-border group">
                            <div
                                class="w-9 h-9 rounded-lg flex items-center justify-center shrink-0"
                                style="background-color: {{ $cred->category?->color }}1A; color: {{ $cred->category?->color }};"
                            >
                                <x-icon :name="$cred->category?->icon ?? 'key-round'" class="w-4 h-4" />
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-sm font-medium truncate group-hover:text-vault-accent transition-colors">{{ $cred->title }}</p>
                                <p class="text-xs text-vault-text-subtle truncate">{{ $cred->username ?: $cred->email ?: '—' }}</p>
                            </div>
                            <span class="text-xs text-vault-text-subtle shrink-0">{{ $cred->last_accessed_at->diffForHumans() }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-vault-text-subtle py-4 text-center">Nothing accessed yet — credentials show up here once viewed.</p>
                    @endforelse
                </x-card>
            </div>

            {{-- Right column: categories breakdown + audit --}}
            <div class="space-y-6">
                <x-card title="By category">
                    @forelse ($byCategory as $cat)
                        <a href="{{ route('credentials.index', ['category' => $cat->slug]) }}" class="flex items-center gap-3 py-2.5 first:pt-0 last:pb-0 border-b last:border-0 border-vault-border-light dark:border-vault-border group">
                            <div
                                class="w-7 h-7 rounded-md flex items-center justify-center shrink-0"
                                style="background-color: {{ $cat->color }}1A; color: {{ $cat->color }};"
                            >
                                <x-icon :name="$cat->icon" class="w-3.5 h-3.5" />
                            </div>
                            <span class="text-sm flex-1 truncate group-hover:text-vault-accent transition-colors">{{ $cat->name }}</span>
                            <span class="text-xs font-medium text-vault-text-subtle">{{ $cat->credentials_count }}</span>
                        </a>
                    @empty
                        <p class="text-sm text-vault-text-subtle py-2">No categories yet.</p>
                    @endforelse
                </x-card>

                <x-card title="Audit log" description="Most recent actions">
                    <div class="space-y-3">
                        @forelse ($auditPreview as $log)
                            <div class="flex items-start gap-2.5">
                                <div class="w-1.5 h-1.5 rounded-full bg-vault-text-subtle/50 mt-2 shrink-0"></div>
                                <div class="flex-1 min-w-0 text-xs">
                                    <p class="text-vault-text-light dark:text-vault-text">
                                        <span class="font-medium">{{ $log->actionLabel }}</span>
                                        @if ($log->credential)
                                            <span class="text-vault-text-subtle">·</span>
                                            <span>{{ $log->credential->title }}</span>
                                        @endif
                                    </p>
                                    <p class="text-vault-text-subtle mt-0.5">{{ $log->created_at->diffForHumans() }}</p>
                                </div>
                            </div>
                        @empty
                            <p class="text-sm text-vault-text-subtle py-2">No activity yet.</p>
                        @endforelse
                    </div>
                    <div class="pt-3 mt-3 border-t border-vault-border-light dark:border-vault-border">
                        <a href="{{ route('audit.index') }}" class="text-xs text-vault-accent hover:text-vault-accent-hover font-medium">View full audit log →</a>
                    </div>
                </x-card>
            </div>
        </div>
    </div>
</x-app-layout>
