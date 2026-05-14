@props([
    'title' => null,
    'description' => null,
    'padding' => 'md',
])

@php
    $paddingClass = match ($padding) {
        'none' => '',
        'sm' => 'p-4',
        'md' => 'p-6',
        'lg' => 'p-8',
        default => 'p-6',
    };
@endphp

<div {{ $attributes->merge(['class' => 'card']) }}>
    @if ($title || $description)
        <div class="px-6 py-4 border-b border-vault-border-light dark:border-vault-border">
            @if ($title)
                <h3 class="text-base font-semibold text-vault-text-light dark:text-vault-text">{{ $title }}</h3>
            @endif
            @if ($description)
                <p class="text-sm text-vault-text-subtle mt-1">{{ $description }}</p>
            @endif
        </div>
        <div class="{{ $paddingClass }}">{{ $slot }}</div>
    @else
        <div class="{{ $paddingClass }}">{{ $slot }}</div>
    @endif
</div>
