@props([
    'title' => null,
    'breadcrumbs' => [],
])

<header class="sticky top-0 z-20 h-16 px-4 lg:px-8
               bg-vault-bg-light/80 dark:bg-vault-bg/80
               backdrop-blur-xl
               border-b border-vault-border-light dark:border-vault-border
               flex items-center justify-between gap-4">

    <div class="flex items-center gap-3 min-w-0">
        <button @click="$store.sidebar.toggle()" class="lg:hidden p-2 -ml-2 rounded-lg hover:bg-vault-border-light/40 dark:hover:bg-vault-surface">
            <x-icon name="menu" class="w-5 h-5" />
        </button>

        <nav class="flex items-center gap-2 text-sm min-w-0">
            @if (! empty($breadcrumbs))
                @foreach ($breadcrumbs as $i => $crumb)
                    @if ($i > 0)
                        <x-icon name="chevron-right" class="w-3.5 h-3.5 text-vault-text-subtle shrink-0" />
                    @endif
                    @if (! empty($crumb['href']) && $i < count($breadcrumbs) - 1)
                        <a href="{{ $crumb['href'] }}" class="text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text truncate">{{ $crumb['label'] }}</a>
                    @else
                        <span class="font-medium truncate">{{ $crumb['label'] }}</span>
                    @endif
                @endforeach
            @elseif ($title)
                <span class="font-medium truncate">{{ $title }}</span>
            @endif
        </nav>
    </div>

    <div class="flex items-center gap-1.5">
        {{-- Global search trigger --}}
        <button
            @click="window.dispatchEvent(new CustomEvent('open-search'))"
            class="hidden sm:flex items-center gap-2 px-3 py-1.5 rounded-lg text-sm
                   bg-vault-border-light/40 dark:bg-vault-surface
                   hover:bg-vault-border-light/60 dark:hover:bg-vault-surface-elevated
                   border border-vault-border-light dark:border-vault-border
                   text-vault-text-subtle transition-all"
        >
            <x-icon name="search" class="w-4 h-4" />
            <span class="text-xs">Search…</span>
            <kbd class="ml-2 px-1.5 py-0.5 rounded text-[10px] font-mono bg-vault-border-light/80 dark:bg-vault-bg border border-vault-border-light dark:border-vault-border">⌘K</kbd>
        </button>

        {{-- Theme toggle --}}
        <button
            @click="$store.theme.toggle()"
            class="p-2 rounded-lg hover:bg-vault-border-light/40 dark:hover:bg-vault-surface text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text transition-all"
            aria-label="Toggle theme"
        >
            <x-icon name="sun" class="w-4 h-4" x-show="$store.theme.current === 'dark'" />
            <x-icon name="moon" class="w-4 h-4" x-show="$store.theme.current === 'light'" />
        </button>
    </div>
</header>
