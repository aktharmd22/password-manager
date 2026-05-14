<div
    x-data="{
        toasts: [],
        add(toast) {
            const id = Date.now() + Math.random();
            this.toasts.push({ id, ...toast });
            setTimeout(() => this.remove(id), toast.duration || 4000);
        },
        remove(id) {
            this.toasts = this.toasts.filter(t => t.id !== id);
        },
    }"
    x-on:toast.window="add($event.detail)"
    x-init="
        @if (session('success')) add({ type: 'success', message: @js(session('success')) }); @endif
        @if (session('error')) add({ type: 'error', message: @js(session('error')) }); @endif
        @if (session('info')) add({ type: 'info', message: @js(session('info')) }); @endif
    "
    class="fixed top-4 right-4 z-[60] flex flex-col gap-2 pointer-events-none"
>
    <template x-for="toast in toasts" :key="toast.id">
        <div
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-x-8"
            x-transition:enter-end="opacity-100 translate-x-0"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-x-0"
            x-transition:leave-end="opacity-0 translate-x-8"
            class="card pointer-events-auto shadow-vault-lg min-w-[280px] max-w-sm"
            :class="{
                'border-l-4 border-l-vault-success': toast.type === 'success',
                'border-l-4 border-l-vault-danger': toast.type === 'error',
                'border-l-4 border-l-vault-warning': toast.type === 'warning',
                'border-l-4 border-l-vault-accent': toast.type === 'info' || !toast.type,
            }"
        >
            <div class="px-4 py-3 flex items-start gap-3">
                <i
                    :data-lucide="{
                        success: 'check-circle-2',
                        error: 'x-circle',
                        warning: 'alert-triangle',
                        info: 'info',
                    }[toast.type] || 'info'"
                    class="w-4 h-4 mt-0.5 shrink-0"
                    :class="{
                        'text-vault-success': toast.type === 'success',
                        'text-vault-danger': toast.type === 'error',
                        'text-vault-warning': toast.type === 'warning',
                        'text-vault-accent': toast.type === 'info' || !toast.type,
                    }"
                    x-init="$nextTick(() => window.lucide?.createIcons())"
                ></i>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium" x-text="toast.title" x-show="toast.title"></p>
                    <p class="text-sm" :class="toast.title ? 'text-vault-text-subtle mt-0.5' : ''" x-text="toast.message"></p>
                </div>
                <button @click="remove(toast.id)" class="text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text">
                    <i data-lucide="x" class="w-4 h-4" x-init="$nextTick(() => window.lucide?.createIcons())"></i>
                </button>
            </div>
        </div>
    </template>
</div>
