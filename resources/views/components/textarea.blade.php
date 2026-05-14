@props([
    'name',
    'label' => null,
    'value' => '',
    'rows' => 4,
    'placeholder' => '',
    'required' => false,
    'hint' => null,
])

@php $errorMessage = $errors->first($name); @endphp

<div {{ $attributes->only('class')->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-vault-text-light dark:text-vault-text">
            {{ $label }}
            @if ($required)<span class="text-vault-danger">*</span>@endif
        </label>
    @endif

    <textarea
        id="{{ $name }}"
        name="{{ $name }}"
        rows="{{ $rows }}"
        placeholder="{{ $placeholder }}"
        @if ($required) required @endif
        {{ $attributes->except('class')->merge([
            'class' => 'input resize-y ' . ($errorMessage ? 'border-vault-danger focus:border-vault-danger focus:ring-vault-danger/30' : ''),
        ]) }}
    >{{ old($name, $value) }}</textarea>

    @if ($errorMessage)
        <p class="text-xs text-vault-danger flex items-center gap-1">
            <x-icon name="alert-circle" class="w-3.5 h-3.5" /> {{ $errorMessage }}
        </p>
    @elseif ($hint)
        <p class="text-xs text-vault-text-subtle">{{ $hint }}</p>
    @endif
</div>
