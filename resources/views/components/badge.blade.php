@props([
    'variant' => 'neutral',
    'icon' => null,
])

@php
    $variantClasses = match ($variant) {
        'primary' => 'bg-vault-accent-soft text-vault-accent',
        'success' => 'bg-vault-success/10 text-vault-success',
        'warning' => 'bg-vault-warning/10 text-vault-warning',
        'danger' => 'bg-vault-danger/10 text-vault-danger',
        'neutral' => 'bg-vault-border-light/60 text-vault-text-light/80 dark:bg-vault-surface-elevated dark:text-vault-text-muted',
        default => 'bg-vault-border-light/60 text-vault-text-light/80 dark:bg-vault-surface-elevated dark:text-vault-text-muted',
    };
@endphp

<span {{ $attributes->merge(['class' => "badge {$variantClasses}"]) }}>
    @if ($icon)
        <x-icon :name="$icon" class="w-3 h-3" />
    @endif
    {{ $slot }}
</span>
