<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="vault-config" content="{{ json_encode([
        'idleTimeoutSeconds' => config('vault.idle_timeout_seconds'),
        'clipboardClearSeconds' => config('vault.clipboard_clear_seconds'),
        'passwordRevealSeconds' => config('vault.password_reveal_seconds'),
        'searchUrl' => route('search'),
        'logoutUrl' => route('logout'),
        'csrfToken' => csrf_token(),
    ]) }}">

    <title>{{ $title ?? config('app.name', 'SecureVault') }} — SecureVault</title>

    {{-- Pre-paint theme application: avoids the light-mode flash on dark-mode reload. --}}
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

    <div class="flex min-h-screen">
        @include('layouts.partials.sidebar')

        <div class="flex-1 flex flex-col min-w-0">
            @include('layouts.partials.header', [
                'title' => $title ?? null,
                'breadcrumbs' => $breadcrumbs ?? [],
            ])

            <main class="flex-1 px-4 lg:px-8 py-6 lg:py-8 max-w-7xl w-full mx-auto">
                {{ $slot ?? '' }}
                @yield('content')
            </main>
        </div>
    </div>

    @include('layouts.partials.toasts')

    {{-- Global search palette (Cmd/Ctrl + K) --}}
    @auth
        @include('layouts.partials.global-search')
    @endauth

</body>
</html>
