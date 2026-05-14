@props([
    'icon' => 'inbox',
    'title' => 'Nothing here yet',
    'description' => null,
])

<div {{ $attributes->merge(['class' => 'flex flex-col items-center justify-center text-center py-16 px-6 animate-fade-in-up']) }}>
    <div class="w-14 h-14 rounded-2xl bg-vault-border-light/60 dark:bg-vault-surface-elevated flex items-center justify-center mb-4 text-vault-text-subtle">
        <x-icon :name="$icon" class="w-7 h-7" />
    </div>
    <h3 class="text-base font-semibold text-vault-text-light dark:text-vault-text">{{ $title }}</h3>
    @if ($description)
        <p class="mt-1.5 text-sm text-vault-text-subtle max-w-md">{{ $description }}</p>
    @endif
    @if (trim($slot))
        <div class="mt-6">{{ $slot }}</div>
    @endif
</div>
