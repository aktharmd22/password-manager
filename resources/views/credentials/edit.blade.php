<x-app-layout
    :title="'Edit ' . $credential->title"
    :breadcrumbs="[
        ['label' => 'Credentials', 'href' => route('credentials.index')],
        ['label' => $credential->title, 'href' => route('credentials.show', $credential)],
        ['label' => 'Edit'],
    ]"
>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Edit credential</h1>
            <p class="mt-1 text-sm text-vault-text-subtle">Last updated {{ $credential->updated_at->diffForHumans() }}.</p>
        </div>

        @include('credentials._form', [
            'credential' => $credential,
            'categories' => $categories,
            'notes' => $notes,
            'customFields' => $customFields,
        ])
    </div>
</x-app-layout>
