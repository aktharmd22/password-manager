<x-guest-layout :title="'Sign in'">

    <div class="px-8 pt-8 pb-2">
        <h1 class="text-xl font-semibold tracking-tight">Welcome back</h1>
        <p class="mt-1 text-sm text-vault-text-subtle">Enter your credentials to access the vault.</p>
    </div>

    <form method="POST" action="{{ route('login') }}" class="px-8 pt-6 pb-8 space-y-4">
        @csrf

        <x-input
            name="email"
            label="Email"
            type="email"
            icon="mail"
            placeholder="you@company.com"
            required
            autofocus
            autocomplete="username"
        />

        <div x-data="{ visible: false }">
            <div class="flex items-center justify-between mb-1.5">
                <label for="password" class="block text-sm font-medium">Password</label>
                @if (Route::has('password.request'))
                    <a href="{{ route('password.request') }}" class="text-xs text-vault-accent hover:text-vault-accent-hover">Forgot?</a>
                @endif
            </div>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-vault-text-subtle">
                    <x-icon name="lock" class="w-4 h-4" />
                </div>
                <input
                    id="password"
                    name="password"
                    :type="visible ? 'text' : 'password'"
                    required
                    autocomplete="current-password"
                    placeholder="Master password"
                    class="input pl-9 pr-10 {{ $errors->has('password') ? 'border-vault-danger focus:border-vault-danger focus:ring-vault-danger/30' : '' }}"
                />
                <button
                    type="button"
                    @click="visible = !visible"
                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-vault-text-subtle hover:text-vault-text-light dark:hover:text-vault-text"
                >
                    <span x-show="!visible"><x-icon name="eye" class="w-4 h-4" /></span>
                    <span x-show="visible" x-cloak><x-icon name="eye-off" class="w-4 h-4" /></span>
                </button>
            </div>
            @error('password')
                <p class="mt-1.5 text-xs text-vault-danger flex items-center gap-1">
                    <x-icon name="alert-circle" class="w-3.5 h-3.5" /> {{ $message }}
                </p>
            @enderror
        </div>

        <label class="flex items-center gap-2 cursor-pointer select-none">
            <input
                type="checkbox"
                name="remember"
                class="w-4 h-4 rounded border-vault-border-light dark:border-vault-border text-vault-accent focus:ring-vault-accent/40 bg-transparent"
            >
            <span class="text-sm text-vault-text-light/80 dark:text-vault-text-muted">Keep me signed in on this device</span>
        </label>

        <x-button type="submit" variant="primary" class="w-full" icon="log-in" iconPosition="right">
            Sign in
        </x-button>

    </form>
</x-guest-layout>
