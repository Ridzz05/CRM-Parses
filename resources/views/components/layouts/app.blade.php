<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" data-theme="light">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title>{{ $title ?? config('app.name', 'CRM Parser') }}</title>
        <link rel="preconnect" href="https://fonts.googleapis.com">
        <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
        <link href="https://fonts.googleapis.com/css2?family=Playwrite+GB+S+Guides:ital@0;1&display=swap" rel="stylesheet">
        <script>
            (() => {
                const stored = localStorage.getItem('crm-theme');
                const theme = stored === 'dark' || stored === 'light'
                    ? stored
                    : (matchMedia('(prefers-color-scheme: dark)').matches ? 'dark' : 'light');

                document.documentElement.dataset.theme = theme;
            })();
        </script>
        @vite(['resources/css/app.css', 'resources/js/app.js'])
    </head>
    <body class="app-shell antialiased">
        <header class="app-header sticky top-0 z-20">
            <div class="mx-auto flex max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
                <a href="{{ auth()->check() ? route('dashboard') : route('login') }}" class="flex items-center gap-3 text-base font-semibold tracking-wide">
                    <span class="grid size-8 place-items-center rounded bg-[var(--app-primary)] text-xs font-semibold text-[var(--app-primary-text)]">CP</span>
                    <span class="app-accent-label text-[0.9em]">CRM Parser</span>
                </a>

                <div class="flex items-center gap-2">
                    <x-ui.theme-toggle />

                    @auth
                        <form method="POST" action="{{ route('logout') }}" data-loading-message="Mengakhiri sesi...">
                            @csrf
                            <x-ui.button variant="secondary" type="submit">Logout</x-ui.button>
                        </form>
                    @endauth
                </div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
            {{ $slot }}
        </main>

        <x-ui.snackbar
            :message="session('status') ?: ($errors->any() ? 'Ada input yang perlu diperbaiki.' : null)"
            :variant="session('status_variant', $errors->any() ? 'danger' : 'info')"
        />
    </body>
</html>
