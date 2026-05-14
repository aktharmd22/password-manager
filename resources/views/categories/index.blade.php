<x-app-layout :title="'Categories'" :breadcrumbs="[['label' => 'Categories']]">
    <div class="space-y-6">
        <div class="flex items-end justify-between flex-wrap gap-4">
            <div>
                <h1 class="text-2xl font-semibold tracking-tight">Categories</h1>
                <p class="mt-1 text-sm text-vault-text-subtle">Organize credentials into groups. Drag handles are coming.</p>
            </div>
            <x-button :href="route('categories.create')" icon="plus">New category</x-button>
        </div>

        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
            @foreach ($categories as $cat)
                <div class="card p-5 group hover:border-vault-accent/40 transition-all">
                    <div class="flex items-start gap-3">
                        <div
                            class="w-11 h-11 rounded-xl flex items-center justify-center shrink-0"
                            style="background-color: {{ $cat->color }}1A; color: {{ $cat->color }};"
                        >
                            <x-icon :name="$cat->icon" class="w-5 h-5" />
                        </div>
                        <div class="flex-1 min-w-0">
                            <h3 class="font-semibold truncate">{{ $cat->name }}</h3>
                            @if ($cat->description)
                                <p class="text-xs text-vault-text-subtle mt-0.5 line-clamp-2">{{ $cat->description }}</p>
                            @endif
                        </div>
                    </div>

                    <div class="mt-4 flex items-center justify-between text-sm">
                        <a href="{{ route('credentials.index', ['category' => $cat->slug]) }}" class="text-vault-accent hover:text-vault-accent-hover">
                            {{ $cat->credentials_count }} {{ Str::plural('credential', $cat->credentials_count) }}
                        </a>
                        <div class="flex items-center gap-0.5">
                            <a href="{{ route('categories.edit', $cat) }}" class="p-1.5 rounded hover:bg-vault-border-light dark:hover:bg-vault-surface text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text" title="Edit">
                                <x-icon name="pencil" class="w-4 h-4" />
                            </a>
                            <form method="POST" action="{{ route('categories.destroy', $cat) }}" onsubmit="return confirm('Delete this category? Only allowed if empty.')" class="inline">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="p-1.5 rounded hover:bg-vault-danger/10 text-vault-text-subtle hover:text-vault-danger" title="Delete">
                                    <x-icon name="trash-2" class="w-4 h-4" />
                                </button>
                            </form>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</x-app-layout>
