<x-app-layout :title="'Audit log'" :breadcrumbs="[['label' => 'Audit log']]">
    <div class="space-y-6">
        <div class="flex items-end justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Audit log</h1>
                <p class="mt-1 text-sm text-vault-text-subtle">Every action recorded. Append-only — entries cannot be deleted.</p>
            </div>
            <form method="GET" action="{{ route('audit.export') }}">
                @foreach ($filters as $k => $v)
                    @if (filled($v))<input type="hidden" name="{{ $k }}" value="{{ $v }}">@endif
                @endforeach
                <x-button type="submit" variant="secondary" icon="download">Export CSV</x-button>
            </form>
        </div>

        {{-- Filters --}}
        <form method="GET" action="{{ route('audit.index') }}" class="card p-4">
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-5 gap-3">
                <div>
                    <label class="block text-xs uppercase tracking-wide text-vault-text-subtle mb-1.5">Action</label>
                    <select name="action" class="input">
                        <option value="">Any</option>
                        @foreach ($actions as $key => $label)
                            <option value="{{ $key }}" {{ $filters['action'] === $key ? 'selected' : '' }}>
                                {{ $label }} ({{ $actionCounts[$key] ?? 0 }})
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide text-vault-text-subtle mb-1.5">Credential</label>
                    <select name="credential_id" class="input">
                        <option value="">Any</option>
                        @foreach ($credentials as $cred)
                            <option value="{{ $cred->id }}" {{ (string) $filters['credential_id'] === (string) $cred->id ? 'selected' : '' }}>
                                {{ Str::limit($cred->title, 30) }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide text-vault-text-subtle mb-1.5">From</label>
                    <input type="date" name="from" value="{{ $filters['from'] }}" class="input">
                </div>
                <div>
                    <label class="block text-xs uppercase tracking-wide text-vault-text-subtle mb-1.5">To</label>
                    <input type="date" name="to" value="{{ $filters['to'] }}" class="input">
                </div>
                <div class="flex items-end gap-2">
                    <x-button type="submit" variant="primary" icon="filter" class="flex-1">Apply</x-button>
                    @if (array_filter($filters))
                        <a href="{{ route('audit.index') }}" class="btn btn-ghost">Clear</a>
                    @endif
                </div>
            </div>
        </form>

        {{-- Table --}}
        <x-card padding="none">
            @if ($logs->isEmpty())
                <x-empty-state icon="scroll-text" title="No audit events match your filters" />
            @else
                <div class="overflow-x-auto">
                    <table class="w-full text-sm">
                        <thead class="text-xs uppercase tracking-wide text-vault-text-subtle border-b border-vault-border-light dark:border-vault-border">
                            <tr>
                                <th class="px-4 py-3 text-left font-medium">When</th>
                                <th class="px-3 py-3 text-left font-medium">Action</th>
                                <th class="px-3 py-3 text-left font-medium hidden md:table-cell">Credential</th>
                                <th class="px-3 py-3 text-left font-medium hidden lg:table-cell">User</th>
                                <th class="px-3 py-3 text-left font-medium hidden xl:table-cell">IP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-vault-border-light dark:divide-vault-border">
                            @foreach ($logs as $log)
                                <tr class="hover:bg-vault-border-light/30 dark:hover:bg-vault-surface-elevated/50">
                                    <td class="px-4 py-3">
                                        <div class="text-sm">{{ $log->created_at->format('M j, g:i:s A') }}</div>
                                        <div class="text-xs text-vault-text-subtle">{{ $log->created_at->diffForHumans() }}</div>
                                    </td>
                                    <td class="px-3 py-3">
                                        @php
                                            $variant = match (true) {
                                                str_contains($log->action, 'failed') => 'danger',
                                                str_contains($log->action, 'delete') => 'warning',
                                                in_array($log->action, ['login', 'logout', '2fa_enabled', 'created']) => 'success',
                                                in_array($log->action, ['revealed', 'copied_password', 'exported']) => 'warning',
                                                default => 'neutral',
                                            };
                                        @endphp
                                        <x-badge :variant="$variant">{{ $log->actionLabel }}</x-badge>
                                    </td>
                                    <td class="px-3 py-3 hidden md:table-cell">
                                        @if ($log->credential)
                                            <a href="{{ route('credentials.show', $log->credential) }}" class="text-vault-accent hover:text-vault-accent-hover truncate inline-block max-w-[200px]">
                                                {{ $log->credential->title }}
                                            </a>
                                        @else
                                            <span class="text-vault-text-subtle">—</span>
                                        @endif
                                    </td>
                                    <td class="px-3 py-3 hidden lg:table-cell text-vault-text-subtle">{{ $log->user?->email ?? '—' }}</td>
                                    <td class="px-3 py-3 hidden xl:table-cell text-vault-text-subtle font-mono text-xs">{{ $log->ip_address ?? '—' }}</td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>

                <div class="px-4 py-3 border-t border-vault-border-light dark:border-vault-border">
                    {{ $logs->links() }}
                </div>
            @endif
        </x-card>
    </div>
</x-app-layout>
