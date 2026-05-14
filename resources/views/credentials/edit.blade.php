<x-app-layout
    :title="'Edit ' . $credential->title"
    :breadcrumbs="[
        ['label' => 'Credentials', 'href' => route('credentials.index')],
        ['label' => $credential->title, 'href' => route('credentials.show', $credential)],
        ['label' => 'Edit'],
    ]"
>
    <div class="space-y-6">
        <div class="flex items-start justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Edit credential</h1>
                <p class="mt-1 text-sm text-vault-text-subtle">Last updated {{ $credential->updated_at->diffForHumans() }}.</p>
            </div>

            {{-- Delete is a sibling form (not nested inside the edit form) so
                 clicking "Save changes" on the main form can never accidentally
                 trigger a destroy request. --}}
            <form
                method="POST"
                action="{{ route('credentials.destroy', $credential) }}"
                onsubmit="return confirm('Move this credential to trash? You can restore it later.');"
            >
                @csrf
                @method('DELETE')
                <x-button type="submit" variant="danger" icon="trash-2">Delete credential</x-button>
            </form>
        </div>

        @include('credentials._form', [
            'credential' => $credential,
            'categories' => $categories,
            'notes' => $notes,
            'customFields' => $customFields,
        ])
    </div>
</x-app-layout>
