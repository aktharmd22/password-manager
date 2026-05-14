<x-app-layout
    :title="'New credential'"
    :breadcrumbs="[
        ['label' => 'Credentials', 'href' => route('credentials.index')],
        ['label' => 'New credential'],
    ]"
>
    <div class="space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Add a credential</h1>
            <p class="mt-1 text-sm text-vault-text-subtle">Encrypted at rest with your APP_KEY. Plaintext never written to disk.</p>
        </div>

        @include('credentials._form', [
            'credential' => null,
            'categories' => $categories,
            'preselectedCategoryId' => $preselectedCategoryId,
            'notes' => null,
            'customFields' => [],
        ])
    </div>
</x-app-layout>
