@props([
    'name' => 'password',
    'label' => 'Password',
    'value' => '',
    'required' => false,
    'showGenerator' => false,
    'showStrength' => true,
    'placeholder' => '',
    'autocomplete' => 'new-password',
])

<div
    x-data="{
        value: @js(old($name, $value)),
        visible: false,
        get score() {
            const pw = this.value || '';
            let s = 0;
            if (pw.length >= 8) s++;
            if (pw.length >= 12) s++;
            if (pw.length >= 16) s++;
            let v = 0;
            if (/[a-z]/.test(pw)) v++;
            if (/[A-Z]/.test(pw)) v++;
            if (/[0-9]/.test(pw)) v++;
            if (/[^A-Za-z0-9]/.test(pw)) v++;
            if (v >= 3) s++;
            if (v === 4 && pw.length >= 16) s++;
            if (/^(.)\1+$/.test(pw)) s = 0;
            return Math.min(4, Math.max(0, s));
        },
        get strengthLabel() {
            return ['Very weak', 'Weak', 'Fair', 'Strong', 'Excellent'][this.score];
        },
        get strengthColor() {
            return ['bg-vault-danger', 'bg-vault-danger', 'bg-vault-warning', 'bg-vault-success', 'bg-vault-success'][this.score];
        },
        async generate() {
            const res = await fetch('{{ route('tools.generator.api') }}', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]').content,
                },
                body: JSON.stringify({ length: {{ config('vault.default_password_length') }} }),
            });
            const data = await res.json();
            this.value = data.password;
            window.dispatchEvent(new CustomEvent('toast', { detail: { type: 'success', message: 'Strong password generated' } }));
        },
    }"
    class="space-y-1.5"
>
    <div class="flex items-center justify-between">
        <label for="{{ $name }}" class="block text-sm font-medium text-vault-text-light dark:text-vault-text">
            {{ $label }}
            @if ($required)<span class="text-vault-danger">*</span>@endif
        </label>
        @if ($showGenerator)
            <button
                type="button"
                @click="generate()"
                class="text-xs text-vault-accent hover:text-vault-accent-hover font-medium flex items-center gap-1"
            >
                <x-icon name="wand-2" class="w-3.5 h-3.5" /> Generate
            </button>
        @endif
    </div>

    <div class="relative">
        <input
            id="{{ $name }}"
            name="{{ $name }}"
            :type="visible ? 'text' : 'password'"
            x-model="value"
            placeholder="{{ $placeholder }}"
            autocomplete="{{ $autocomplete }}"
            @if ($required) required @endif
            class="input pr-10 font-mono {{ $errors->has($name) ? 'border-vault-danger focus:border-vault-danger focus:ring-vault-danger/30' : '' }}"
        />
        <button
            type="button"
            @click="visible = !visible"
            class="absolute inset-y-0 right-0 pr-3 flex items-center text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text"
            :aria-label="visible ? 'Hide password' : 'Show password'"
        >
            <x-icon name="eye" class="w-4 h-4" x-show="!visible" />
            <x-icon name="eye-off" class="w-4 h-4" x-show="visible" x-cloak />
        </button>
    </div>

    @if ($showStrength)
        <div class="flex items-center gap-2 pt-1" x-show="value.length > 0" x-cloak>
            <div class="flex-1 h-1 rounded-full bg-vault-border-light dark:bg-vault-border overflow-hidden">
                <div
                    class="h-full transition-all duration-300"
                    :class="strengthColor"
                    :style="`width: ${(score + 1) * 20}%`"
                ></div>
            </div>
            <span class="text-xs font-medium text-vault-text-subtle" x-text="strengthLabel"></span>
        </div>
    @endif

    @error($name)
        <p class="text-xs text-vault-danger flex items-center gap-1">
            <x-icon name="alert-circle" class="w-3.5 h-3.5" /> {{ $message }}
        </p>
    @enderror
</div>
