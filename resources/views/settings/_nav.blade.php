@php
    $tabs = [
        ['route' => 'settings.profile',     'label' => 'Profile',     'icon' => 'user'],
        ['route' => 'settings.security',    'label' => 'Security',    'icon' => 'shield'],
        ['route' => 'settings.preferences', 'label' => 'Preferences', 'icon' => 'sliders-horizontal'],
        ['route' => 'settings.backup',      'label' => 'Backup',      'icon' => 'database-backup'],
    ];
@endphp

<nav class="flex items-center gap-1 overflow-x-auto pb-2 -mb-2 border-b border-vault-border-light dark:border-vault-border">
    @foreach ($tabs as $tab)
        @php $active = request()->routeIs($tab['route']); @endphp
        <a
            href="{{ route($tab['route']) }}"
            class="inline-flex items-center gap-2 px-3 py-2 text-sm rounded-lg transition-all whitespace-nowrap
                   {{ $active
                      ? 'bg-vault-accent-soft text-vault-accent font-medium'
                      : 'text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text hover:bg-vault-border-light/40 dark:hover:bg-vault-surface-elevated' }}"
        >
            <x-icon :name="$tab['icon']" class="w-4 h-4" />
            <span>{{ $tab['label'] }}</span>
        </a>
    @endforeach
</nav>
