@props([
    'variant' => 'primary',
    'size' => 'md',
    'type' => 'button',
    'href' => null,
    'icon' => null,
    'iconPosition' => 'left',
    'loading' => false,
])

@php
    $variantClasses = match ($variant) {
        'primary' => 'btn-primary',
        'secondary' => 'btn-secondary',
        'ghost' => 'btn-ghost',
        'danger' => 'btn-danger',
        default => 'btn-primary',
    };
    $sizeClasses = match ($size) {
        'xs' => 'px-2 py-1 text-xs',
        'sm' => 'px-3 py-1.5 text-sm',
        'md' => 'px-4 py-2 text-sm',
        'lg' => 'px-5 py-2.5 text-base',
        default => 'px-4 py-2 text-sm',
    };
    $classes = "btn {$variantClasses} {$sizeClasses}";
@endphp

@if ($href)
    <a href="{{ $href }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($icon && $iconPosition === 'left')
            <x-icon :name="$icon" class="w-4 h-4" />
        @endif
        {{ $slot }}
        @if ($icon && $iconPosition === 'right')
            <x-icon :name="$icon" class="w-4 h-4" />
        @endif
    </a>
@else
    <button type="{{ $type }}" {{ $attributes->merge(['class' => $classes]) }}>
        @if ($loading)
            <x-icon name="loader-2" class="w-4 h-4 animate-spin" />
        @elseif ($icon && $iconPosition === 'left')
            <x-icon :name="$icon" class="w-4 h-4" />
        @endif
        {{ $slot }}
        @if ($icon && $iconPosition === 'right' && ! $loading)
            <x-icon :name="$icon" class="w-4 h-4" />
        @endif
    </button>
@endif
