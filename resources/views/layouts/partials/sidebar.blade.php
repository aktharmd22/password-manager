@php
    $nav = [
        ['route' => 'dashboard',        'label' => 'Dashboard',    'icon' => 'layout-dashboard'],
        ['route' => 'credentials.index','label' => 'Credentials',  'icon' => 'key-round'],
        ['route' => 'categories.index', 'label' => 'Categories',   'icon' => 'folder-tree'],
        ['route' => 'tools.generator',  'label' => 'Generator',    'icon' => 'wand-2'],
        ['route' => 'audit.index',      'label' => 'Audit log',    'icon' => 'scroll-text'],
    ];
@endphp

<aside
    x-data
    :class="$store.sidebar.open ? 'translate-x-0' : '-translate-x-full lg:translate-x-0'"
    class="fixed lg:sticky top-0 left-0 z-40 w-[280px] h-screen
           bg-white dark:bg-vault-surface
           border-r border-vault-border-light dark:border-vault-border
           flex flex-col transition-transform duration-200 ease-out
           shrink-0"
>
    {{-- Logo --}}
    <div class="h-16 px-5 flex items-center justify-between border-b border-vault-border-light dark:border-vault-border">
        <a href="{{ route('dashboard') }}" class="flex items-center gap-2.5">
            <div class="w-8 h-8 rounded-lg bg-gradient-to-br from-vault-accent to-vault-accent-hover flex items-center justify-center shadow-vault-glow">
                <x-icon name="shield-check" class="w-4.5 h-4.5 text-white" />
            </div>
            <div class="flex flex-col leading-none">
                <span class="text-[15px] font-semibold tracking-tight">SecureVault</span>
                <span class="text-[10px] uppercase tracking-widest text-vault-text-subtle mt-0.5">Internal</span>
            </div>
        </a>
        <button @click="$store.sidebar.close()" class="lg:hidden text-vault-text-subtle">
            <x-icon name="x" class="w-5 h-5" />
        </button>
    </div>

    {{-- Quick action --}}
    <div class="px-4 pt-4">
        <a href="{{ route('credentials.create') }}" class="btn btn-primary w-full">
            <x-icon name="plus" class="w-4 h-4" /> New credential
        </a>
    </div>

    {{-- Navigation --}}
    <nav class="flex-1 px-3 pt-4 pb-4 overflow-y-auto">
        <p class="px-3 mb-2 text-[10px] uppercase tracking-widest font-medium text-vault-text-subtle">Main</p>
        <div class="space-y-0.5">
            @foreach ($nav as $item)
                @php $active = request()->routeIs($item['route']) || request()->routeIs($item['route'] . '.*'); @endphp
                <a
                    href="{{ route($item['route']) }}"
                    class="nav-link {{ $active ? 'nav-link-active' : '' }}"
                >
                    <x-icon :name="$item['icon']" class="w-4 h-4 shrink-0" />
                    <span>{{ $item['label'] }}</span>
                </a>
            @endforeach
        </div>

        <p class="px-3 mt-6 mb-2 text-[10px] uppercase tracking-widest font-medium text-vault-text-subtle">Account</p>
        <div class="space-y-0.5">
            <a href="{{ route('settings.profile') }}" class="nav-link {{ request()->routeIs('settings.*') ? 'nav-link-active' : '' }}">
                <x-icon name="settings" class="w-4 h-4 shrink-0" />
                <span>Settings</span>
            </a>
        </div>
    </nav>

    {{-- User menu --}}
    <div class="border-t border-vault-border-light dark:border-vault-border p-3" x-data="{ open: false }">
        <button
            @click="open = !open"
            class="w-full flex items-center gap-3 p-2 rounded-lg hover:bg-vault-border-light/40 dark:hover:bg-vault-surface-elevated transition-all"
        >
            <div class="w-9 h-9 rounded-full bg-gradient-to-br from-vault-accent to-vault-accent-hover text-white flex items-center justify-center text-sm font-semibold shrink-0">
                {{ strtoupper(substr(auth()->user()->name ?? 'U', 0, 1)) }}
            </div>
            <div class="flex-1 min-w-0 text-left">
                <p class="text-sm font-medium truncate">{{ auth()->user()->name ?? 'Admin' }}</p>
                <p class="text-xs text-vault-text-subtle truncate">{{ auth()->user()->email ?? '' }}</p>
            </div>
            <x-icon name="chevron-up" class="w-4 h-4 text-vault-text-subtle" />
        </button>

        <div
            x-show="open"
            x-transition.opacity
            @click.outside="open = false"
            class="mt-2 space-y-0.5"
            x-cloak
        >
            <a href="{{ route('settings.security') }}" class="nav-link">
                <x-icon name="shield" class="w-4 h-4" /> Security
            </a>
            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button type="submit" class="nav-link w-full text-left text-vault-danger hover:text-vault-danger">
                    <x-icon name="log-out" class="w-4 h-4" /> Sign out
                </button>
            </form>
        </div>
    </div>
</aside>

{{-- Backdrop on mobile --}}
<div
    x-show="$store.sidebar.open"
    @click="$store.sidebar.close()"
    x-transition.opacity
    class="fixed inset-0 z-30 bg-black/40 lg:hidden"
    x-cloak
></div>
