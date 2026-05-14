@props([
    'label',
    'value',
    'icon' => null,
    'iconColor' => '#6366F1',
    'trend' => null,
    'href' => null,
])

@php
    $wrapper = $href ? 'a' : 'div';
@endphp

<{{ $wrapper }}
    @if ($href) href="{{ $href }}" @endif
    {{ $attributes->merge(['class' => 'card p-5 ' . ($href ? 'block hover:border-vault-accent/40 hover:shadow-vault transition-all duration-200' : '')]) }}
>
    <div class="flex items-start justify-between gap-4">
        <div class="min-w-0">
            <p class="text-xs uppercase tracking-wide font-medium text-vault-text-subtle">{{ $label }}</p>
            <p class="mt-2 text-3xl font-semibold tracking-tight text-vault-text-light dark:text-vault-text">{{ $value }}</p>
            @if ($trend)
                <p class="mt-1 text-xs text-vault-text-subtle">{{ $trend }}</p>
            @endif
        </div>
        @if ($icon)
            <div
                class="w-10 h-10 rounded-xl flex items-center justify-center shrink-0"
                style="background-color: {{ $iconColor }}1A; color: {{ $iconColor }};"
            >
                <x-icon :name="$icon" class="w-5 h-5" />
            </div>
        @endif
    </div>
</{{ $wrapper }}>
