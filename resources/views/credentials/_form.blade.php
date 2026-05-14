@props([
    'credential' => null,
    'categories',
    'notes' => null,
    'customFields' => [],
    'preselectedCategoryId' => null,
])

@php
    $isEdit = $credential !== null;
    $catOptions = $categories->mapWithKeys(fn ($c) => [$c->id => $c->name])->all();
    $currentTags = $isEdit ? ($credential->tags ?? []) : [];
@endphp

<form
    method="POST"
    action="{{ $isEdit ? route('credentials.update', $credential) : route('credentials.store') }}"
    x-data="credentialForm()"
    class="grid grid-cols-1 lg:grid-cols-3 gap-6"
>
    @csrf
    @if ($isEdit) @method('PATCH') @endif

    {{-- Left column --}}
    <div class="lg:col-span-2 space-y-4">
        <x-card title="Basic info">
            <div class="space-y-4">
                <x-input
                    name="title"
                    label="Title"
                    placeholder="e.g. AWS Production Root Account"
                    icon="key-round"
                    :value="$credential?->title ?? ''"
                    required
                />

                <x-select
                    name="category_id"
                    label="Category"
                    :options="$catOptions"
                    :value="$credential?->category_id ?? $preselectedCategoryId ?? ''"
                    required
                />

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    <x-input
                        name="username"
                        label="Username"
                        icon="user"
                        autocomplete="off"
                        :value="$credential?->username ?? ''"
                    />
                    <x-input
                        name="email"
                        type="email"
                        label="Email"
                        icon="mail"
                        autocomplete="off"
                        :value="$credential?->email ?? ''"
                    />
                </div>

                <x-password-field
                    name="password"
                    :label="$isEdit ? 'New password (leave blank to keep current)' : 'Password'"
                    :required="!$isEdit"
                    show-generator="true"
                    show-strength="true"
                    placeholder="{{ $isEdit ? '••••••••••••' : 'Generate or enter a strong password' }}"
                />

                <x-input
                    name="url"
                    label="URL"
                    type="url"
                    icon="link"
                    placeholder="https://"
                    :value="$credential?->url ?? ''"
                />
            </div>
        </x-card>

        <x-card title="Notes" description="Encrypted at rest. Markdown is not rendered.">
            <x-textarea
                name="notes"
                :value="$notes"
                rows="5"
                placeholder="Any additional context, recovery info, security questions, etc."
            />
        </x-card>

        <x-card title="Custom fields" description="For anything that doesn't fit the standard fields. All values encrypted.">
            <div class="space-y-2">
                <template x-for="(field, idx) in customFields" :key="idx">
                    <div class="flex items-center gap-2">
                        <input
                            type="text"
                            :name="`custom_fields[${idx}][key]`"
                            x-model="field.key"
                            placeholder="Field name (e.g. API endpoint)"
                            class="input flex-1"
                        />
                        <input
                            type="text"
                            :name="`custom_fields[${idx}][value]`"
                            x-model="field.value"
                            placeholder="Value"
                            class="input flex-1 font-mono"
                        />
                        <button
                            type="button"
                            @click="removeCustomField(idx)"
                            class="p-2 text-vault-text-subtle hover:text-vault-danger"
                            title="Remove field"
                        >
                            <x-icon name="x" class="w-4 h-4" />
                        </button>
                    </div>
                </template>

                <button
                    type="button"
                    @click="addCustomField()"
                    class="btn btn-ghost text-sm w-full justify-center border border-dashed border-vault-border-light dark:border-vault-border"
                >
                    <x-icon name="plus" class="w-4 h-4" /> Add custom field
                </button>
            </div>
        </x-card>
    </div>

    {{-- Right column --}}
    <div class="space-y-4">
        <x-card title="Organization">
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium mb-1.5">Tags</label>
                    <div class="flex items-center gap-1.5 flex-wrap p-2 rounded-lg border border-vault-border-light dark:border-vault-border min-h-[42px]">
                        <template x-for="(tag, idx) in tags" :key="idx">
                            <span class="badge bg-vault-accent-soft text-vault-accent">
                                #<span x-text="tag"></span>
                                <button type="button" @click="removeTag(idx)" class="hover:opacity-80 -mr-0.5">
                                    <x-icon name="x" class="w-3 h-3" />
                                </button>
                                <input type="hidden" :name="`tags[]`" :value="tag">
                            </span>
                        </template>
                        <input
                            type="text"
                            x-model="newTag"
                            @keydown.enter.prevent="addTag()"
                            @keydown.,.prevent="addTag()"
                            @keydown.backspace="newTag === '' && tags.length > 0 && tags.pop()"
                            placeholder="Add tag…"
                            class="flex-1 min-w-[100px] bg-transparent border-none outline-none text-sm placeholder:text-vault-text-subtle focus:ring-0 p-1"
                        />
                    </div>
                    <p class="text-xs text-vault-text-subtle mt-1.5">Press Enter or comma to add. Tags help filter the list.</p>
                </div>

                <label class="flex items-center gap-2 cursor-pointer select-none">
                    <input
                        type="hidden" name="is_favorite" value="0"
                    >
                    <input
                        type="checkbox"
                        name="is_favorite"
                        value="1"
                        @if (old('is_favorite', $credential?->is_favorite ?? false)) checked @endif
                        class="w-4 h-4 rounded text-vault-accent focus:ring-vault-accent/40 bg-transparent border-vault-border-light dark:border-vault-border"
                    >
                    <x-icon name="star" class="w-4 h-4 text-vault-warning" />
                    <span class="text-sm">Mark as favorite</span>
                </label>
            </div>
        </x-card>

        <x-card>
            <div class="space-y-2">
                <x-button type="submit" variant="primary" icon="check" class="w-full">
                    {{ $isEdit ? 'Save changes' : 'Create credential' }}
                </x-button>
                <x-button
                    type="button"
                    variant="ghost"
                    :href="$isEdit ? route('credentials.show', $credential) : route('credentials.index')"
                    class="w-full"
                >
                    Cancel
                </x-button>
            </div>
        </x-card>

        @if ($isEdit)
            <x-card>
                <form method="POST" action="{{ route('credentials.destroy', $credential) }}" onsubmit="return confirm('Move this credential to trash? You can restore it later.');">
                    @csrf
                    @method('DELETE')
                    <x-button type="submit" variant="danger" icon="trash-2" class="w-full">Delete credential</x-button>
                </form>
            </x-card>
        @endif
    </div>

    <script>
        function credentialForm() {
            return {
                tags: @js($currentTags),
                newTag: '',
                customFields: @js(empty($customFields) ? [['key' => '', 'value' => '']] : $customFields),

                addTag() {
                    const t = this.newTag.trim().replace(/^#+/, '');
                    if (t && !this.tags.includes(t) && t.length <= 64) {
                        this.tags.push(t);
                    }
                    this.newTag = '';
                },

                removeTag(idx) { this.tags.splice(idx, 1); },

                addCustomField() {
                    this.customFields.push({ key: '', value: '' });
                },

                removeCustomField(idx) {
                    this.customFields.splice(idx, 1);
                    if (this.customFields.length === 0) {
                        this.customFields.push({ key: '', value: '' });
                    }
                },
            };
        }
    </script>
</form>
