@props([
    'label',
    'icon' => 'circle',
    'value',
    'copyField' => null,
    'credentialId' => null,
    'mono' => false,
])

<div class="flex items-center gap-3 py-2">
    <div class="w-8 h-8 rounded-lg bg-vault-border-light/50 dark:bg-vault-bg/40 flex items-center justify-center text-vault-text-subtle shrink-0">
        <x-icon :name="$icon" class="w-4 h-4" />
    </div>
    <div class="flex-1 min-w-0">
        <p class="text-xs text-vault-text-subtle">{{ $label }}</p>
        <p class="text-sm truncate {{ $mono ? 'font-mono' : '' }}">{{ $value }}</p>
    </div>
    @if ($copyField && $credentialId)
        <button
            type="button"
            onclick="window.copyCredentialField(this)"
            data-credential-id="{{ $credentialId }}"
            data-field="{{ $copyField }}"
            data-label="{{ $label }}"
            class="p-2 rounded hover:bg-vault-border-light dark:hover:bg-vault-surface text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text"
            title="Copy"
        >
            <x-icon name="copy" class="w-4 h-4" />
        </button>
    @endif
</div>
