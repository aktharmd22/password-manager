<x-app-layout
    :title="'Edit ' . $category->name"
    :breadcrumbs="[['label' => 'Categories', 'href' => route('categories.index')], ['label' => 'Edit ' . $category->name]]"
>
    <div class="max-w-2xl space-y-6">
        <div>
            <h1 class="text-2xl font-semibold tracking-tight">Edit category</h1>
        </div>

        @include('categories._form', ['category' => $category, 'isEdit' => true])
    </div>
</x-app-layout>
