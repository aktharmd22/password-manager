<x-app-layout
    :title="'New category'"
    :breadcrumbs="[['label' => 'Categories', 'href' => route('categories.index')], ['label' => 'New']]"
>
    <div class="max-w-2xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">New category</h1>
        </div>

        @include('categories._form', ['category' => $category, 'isEdit' => false])
    </div>
</x-app-layout>
