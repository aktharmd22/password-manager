@props([
    'name',
    'label' => null,
    'options' => [],
    'value' => '',
    'placeholder' => 'Select…',
    'required' => false,
])

@php $errorMessage = $errors->first($name); $current = old($name, $value); @endphp

<div {{ $attributes->only('class')->merge(['class' => 'space-y-1.5']) }}>
    @if ($label)
        <label for="{{ $name }}" class="block text-sm font-medium text-vault-text-light dark:text-vault-text">
            {{ $label }}
            @if ($required)<span class="text-vault-danger">*</span>@endif
        </label>
    @endif

    <select
        id="{{ $name }}"
        name="{{ $name }}"
        @if ($required) required @endif
        {{ $attributes->except('class')->merge([
            'class' => 'input ' . ($errorMessage ? 'border-vault-danger focus:border-vault-danger focus:ring-vault-danger/30' : ''),
        ]) }}
    >
        <option value="" {{ $current === '' || $current === null ? 'selected' : '' }} disabled>{{ $placeholder }}</option>
        @foreach ($options as $key => $option)
            <option value="{{ $key }}" {{ (string) $current === (string) $key ? 'selected' : '' }}>
                {{ $option }}
            </option>
        @endforeach
    </select>

    @if ($errorMessage)
        <p class="text-xs text-vault-danger flex items-center gap-1">
            <x-icon name="alert-circle" class="w-3.5 h-3.5" /> {{ $errorMessage }}
        </p>
    @endif
</div>
