<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">

    <title>{{ $title ?? 'Sign in' }} — SecureVault</title>

    <script>
        (function () {
            const stored = localStorage.getItem('vault-theme');
            const theme = stored || 'dark';
            if (theme === 'dark') document.documentElement.classList.add('dark');
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="font-sans antialiased min-h-screen bg-vault-bg-light dark:bg-vault-bg text-vault-text-light dark:text-vault-text">

    <div class="min-h-screen flex flex-col items-center justify-center px-4 py-12 relative overflow-hidden">

        {{-- Subtle backdrop --}}
        <div class="absolute inset-0 -z-10 opacity-50">
            <div class="absolute top-0 left-1/2 -translate-x-1/2 w-[800px] h-[400px] rounded-full bg-vault-accent/20 blur-[120px]"></div>
        </div>

        {{-- Logo --}}
        <a href="/" class="flex items-center gap-2.5 mb-8">
            <div class="w-10 h-10 rounded-xl bg-gradient-to-br from-vault-accent to-vault-accent-hover flex items-center justify-center shadow-vault-glow">
                <x-icon name="shield-check" class="w-5 h-5 text-white" />
            </div>
            <div class="flex flex-col leading-none">
                <span class="text-base font-semibold tracking-tight">SecureVault</span>
                <span class="text-[10px] uppercase tracking-widest text-vault-text-subtle mt-0.5">Internal Password Manager</span>
            </div>
        </a>

        {{-- Card --}}
        <div class="w-full max-w-md card shadow-vault-lg animate-fade-in-up">
            {{ $slot }}
        </div>

        <p class="mt-6 text-xs text-vault-text-subtle">
            Authorized personnel only · v{{ config('app.version', '1.0') }}
        </p>
    </div>

    @include('layouts.partials.toasts')
</body>
</html>
