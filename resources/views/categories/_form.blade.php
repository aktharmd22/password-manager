@props(['category', 'isEdit' => false])

@php
    $iconOptions = ['folder', 'mail', 'server', 'globe', 'database', 'folder-up', 'key-round',
                    'at-sign', 'landmark', 'badge-check', 'lock', 'shield', 'cloud', 'wifi',
                    'credit-card', 'bookmark', 'package', 'briefcase', 'building', 'archive',
                    'cpu', 'hard-drive', 'monitor', 'smartphone', 'router'];
    $colorPresets = ['#6366F1', '#EA4335', '#10B981', '#F59E0B', '#06B6D4', '#8B5CF6',
                     '#EC4899', '#16A34A', '#F97316', '#71717A', '#3B82F6', '#EF4444'];
@endphp

<form
    method="POST"
    action="{{ $isEdit ? route('categories.update', $category) : route('categories.store') }}"
    x-data="{
        icon: @js(old('icon', $category->icon)),
        color: @js(old('color', $category->color)),
    }"
    class="space-y-6"
>
    @csrf
    @if ($isEdit) @method('PATCH') @endif

    <x-card>
        <div class="space-y-4">
            <div class="flex items-center gap-4">
                <div
                    class="w-16 h-16 rounded-2xl flex items-center justify-center shrink-0 transition-colors"
                    :style="`background-color: ${color}1A; color: ${color};`"
                >
                    <i :data-lucide="icon" class="w-7 h-7" x-init="$nextTick(() => window.lucide?.createIcons())"></i>
                </div>
                <div class="flex-1">
                    <x-input
                        name="name"
                        label="Name"
                        :value="$category->name ?? ''"
                        placeholder="e.g. Servers, Databases, …"
                        required
                    />
                </div>
            </div>

            <x-input
                name="description"
                label="Description"
                :value="$category->description ?? ''"
                placeholder="Optional — short hint shown under the category name"
            />

            <x-input
                name="sort_order"
                type="number"
                label="Sort order"
                :value="$category->sort_order ?? 0"
                min="0"
                hint="Lower numbers appear first."
            />

            <div>
                <label class="block text-sm font-medium mb-2">Icon</label>
                <div class="flex flex-wrap gap-1.5 mb-2">
                    @foreach ($iconOptions as $opt)
                        <button
                            type="button"
                            @click="icon = @js($opt); $nextTick(() => window.lucide?.createIcons())"
                            :class="icon === @js($opt) ? 'bg-vault-accent-soft text-vault-accent border-vault-accent/40' : 'hover:bg-vault-border-light/40 dark:hover:bg-vault-surface-elevated text-vault-text-subtle border-vault-border-light dark:border-vault-border'"
                            class="w-9 h-9 rounded-lg border flex items-center justify-center transition-all"
                            :title="@js($opt)"
                        >
                            <i data-lucide="{{ $opt }}" class="w-4 h-4"></i>
                        </button>
                    @endforeach
                </div>
                <input
                    type="text"
                    name="icon"
                    x-model="icon"
                    class="input font-mono text-xs"
                    placeholder="lucide icon name (e.g. folder, key-round)"
                    required
                />
                <p class="text-xs text-vault-text-subtle mt-1.5">Any <a href="https://lucide.dev/icons" target="_blank" rel="noopener" class="text-vault-accent hover:text-vault-accent-hover">Lucide icon name</a> works.</p>
            </div>

            <div>
                <label class="block text-sm font-medium mb-2">Color</label>
                <div class="flex flex-wrap gap-1.5 mb-2">
                    @foreach ($colorPresets as $hex)
                        <button
                            type="button"
                            @click="color = @js($hex)"
                            :class="color === @js($hex) ? 'ring-2 ring-offset-2 ring-vault-text-light dark:ring-vault-text ring-offset-vault-bg-light dark:ring-offset-vault-bg' : ''"
                            class="w-8 h-8 rounded-lg transition-all"
                            style="background-color: {{ $hex }};"
                            :title="@js($hex)"
                        ></button>
                    @endforeach
                </div>
                <div class="flex items-center gap-2">
                    <input type="color" x-model="color" class="w-12 h-10 rounded-lg cursor-pointer border-0 p-0 bg-transparent">
                    <input type="text" name="color" x-model="color" pattern="^#[0-9a-fA-F]{6}$" class="input font-mono text-sm flex-1" required>
                </div>
            </div>
        </div>
    </x-card>

    <div class="flex items-center gap-2 justify-end">
        <x-button variant="ghost" :href="route('categories.index')">Cancel</x-button>
        <x-button type="submit" variant="primary" icon="check">{{ $isEdit ? 'Save changes' : 'Create category' }}</x-button>
    </div>
</form>
