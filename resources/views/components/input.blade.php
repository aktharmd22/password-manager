@props([
    'name',
    'label' => null,
    'type' => 'text',
    'value' => '',
    'placeholder' => '',
    'required' => false,
    'icon' => null,
    'hint' => null,
    'error' => null,
])

@php $errorMessage = $error ?? $errors->first($name); @endphp

<div {{ $attributes->only('class')->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-vault-text-light dark:text-vault-text">
            {{ $label }}
            @if ($required)<span class="text-vault-danger">*</span>@endif
        </label>
    @endif

    <div class="relative">
        @if ($icon)
            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-vault-text-subtle">
                <x-icon :name="$icon" class="w-4 h-4" />
            </div>
        @endif

        <input
            id="{{ $name }}"
            name="{{ $name }}"
            type="{{ $type }}"
            value="{{ old($name, $value) }}"
            placeholder="{{ $placeholder }}"
            @if ($required) required @endif
            {{ $attributes->except('class')->merge([
                'class' => 'input ' . ($icon ? 'pl-9 ' : '') . ($errorMessage ? 'border-vault-danger focus:border-vault-danger focus:ring-vault-danger/30' : ''),
            ]) }}
        />
    </div>

    @if ($errorMessage)
        <p class="text-xs text-vault-danger flex items-center gap-1">
            <x-icon name="alert-circle" class="w-3.5 h-3.5" /> {{ $errorMessage }}
        </p>
    @elseif ($hint)
        <p class="text-xs text-vault-text-subtle">{{ $hint }}</p>
    @endif
</div>
