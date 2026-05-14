@props([
    'name',
    'title' => 'Are you sure?',
    'description' => null,
    'confirmText' => 'Confirm',
    'cancelText' => 'Cancel',
    'variant' => 'danger',
    'action' => '',
    'method' => 'POST',
])

<x-modal :name="$name" :title="$title" :description="$description" maxWidth="md">
    <form action="{{ $action }}" method="POST" class="space-y-4">
        @csrf
        @if (strtoupper($method) !== 'POST')
            @method($method)
        @endif

        {{ $slot }}

        <div class="flex items-center justify-end gap-2 pt-2">
            <x-button variant="ghost" @click="show = false" type="button">{{ $cancelText }}</x-button>
            <x-button :variant="$variant" type="submit">{{ $confirmText }}</x-button>
        </div>
    </form>
</x-modal>
