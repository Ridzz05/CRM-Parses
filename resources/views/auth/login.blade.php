<x-layouts.app title="Login Admin">
    <div class="mx-auto grid max-w-5xl gap-8 lg:grid-cols-[1fr_28rem] lg:items-center">
        <div class="hidden lg:block">
            <x-ui.badge variant="info" accent>Operational CRM</x-ui.badge>
            <h1 class="mt-4 max-w-xl text-4xl font-semibold leading-tight">Parser transaksi berbasis instruksi teks.</h1>
            <p class="mt-4 max-w-lg text-sm leading-6 text-[var(--app-muted)]">Kelola input manual, webhook Telegram, WhatsApp Business, Instagram, hasil parsing AI, pelanggan, dan transaksi dari satu dashboard.</p>
        </div>

        <x-ui.card title="Login Admin" description="Masuk sebagai {{ $adminName ?? 'Admin' }} untuk mengelola CRM Parser.">
            <form method="POST" action="{{ route('login.store') }}" class="space-y-4" data-loading-message="Memeriksa kredensial...">
                @csrf

                <div>
                    <label class="block text-sm font-medium" for="email">Email</label>
                    <x-ui.input id="email" name="email" type="email" value="{{ old('email', $adminEmail ?? '') }}" required autofocus class="mt-2" />
                    @error('email')
                        <p class="mt-2 text-sm text-[var(--app-danger)]">{{ $message }}</p>
                    @enderror
                </div>

                <div>
                    <label class="block text-sm font-medium" for="password">Password</label>
                    <x-ui.input id="password" name="password" type="password" value="{{ $adminPassword ?? '' }}" required class="mt-2" />
                    @error('password')
                        <p class="mt-2 text-sm text-[var(--app-danger)]">{{ $message }}</p>
                    @enderror
                </div>

                <div class="rounded-md border border-[var(--app-border)] bg-[var(--app-soft)] px-3 py-2 text-xs text-[var(--app-muted)]">
                    Admin default: <span class="font-medium text-[var(--app-text)]">{{ $adminName ?? 'Admin' }}</span>
                    <span class="mx-1 text-[var(--app-border-strong)]">/</span>
                    <span class="font-medium text-[var(--app-text)]">{{ $adminEmail ?? '-' }}</span>
                </div>

                <label class="flex items-center gap-2 text-sm text-[var(--app-muted)]">
                    <input name="remember" type="checkbox" value="1" class="rounded border-[var(--app-border)] bg-[var(--app-bg)] text-[var(--app-primary)]">
                    Ingat sesi ini
                </label>

                <x-ui.button type="submit" class="w-full">Login</x-ui.button>
            </form>
        </x-ui.card>
    </div>
</x-layouts.app>
