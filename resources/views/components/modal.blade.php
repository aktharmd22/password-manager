@props([
    'name',
    'show' => false,
    'maxWidth' => '2xl',
    'title' => null,
    'description' => null,
])

@php
    $maxWidth = [
        'sm' => 'sm:max-w-sm',
        'md' => 'sm:max-w-md',
        'lg' => 'sm:max-w-lg',
        'xl' => 'sm:max-w-xl',
        '2xl' => 'sm:max-w-2xl',
        '3xl' => 'sm:max-w-3xl',
    ][$maxWidth];
@endphp

<div
    x-data="{ show: @js($show) }"
    x-on:open-modal.window="if ($event.detail === '{{ $name }}') show = true"
    x-on:close-modal.window="if ($event.detail === '{{ $name }}') show = false"
    x-on:keydown.escape.window="show = false"
    x-show="show"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
    x-cloak
>
    <div class="flex items-center justify-center min-h-screen px-4 py-6 sm:p-0">
        <div
            x-show="show"
            x-transition.opacity
            class="fixed inset-0 glass"
            @click="show = false"
        ></div>

        <div
            x-show="show"
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0 translate-y-4 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:scale-95"
            class="relative w-full {{ $maxWidth }} card shadow-vault-lg"
        >
            @if ($title || $description)
                <div class="px-6 py-4 border-b border-vault-border-light dark:border-vault-border flex items-start justify-between gap-4">
                    <div>
                        @if ($title)
                            <h3 class="text-base font-semibold tracking-tight">{{ $title }}</h3>
                        @endif
                        @if ($description)
                            <p class="mt-1 text-sm text-vault-text-subtle">{{ $description }}</p>
                        @endif
                    </div>
                    <button @click="show = false" class="text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text">
                        <x-icon name="x" class="w-5 h-5" />
                    </button>
                </div>
            @endif
            <div class="p-6">{{ $slot }}</div>
        </div>
    </div>
</div>
