<x-layouts.app title="Debug OpenRouter">
    <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
            <x-ui.button variant="ghost" :href="route('dashboard')" class="-ml-4">Kembali ke dashboard</x-ui.button>
            <h1 class="mt-2 text-3xl font-semibold">Debug OpenRouter</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-[var(--app-muted)]">Tes endpoint parser OpenRouter dengan payload yang sama seperti flow instruksi.</p>
        </div>
    </div>

    <x-ui.card title="Test Request" description="Gunakan teks transaksi contoh untuk mengecek API key, model, HTTP status, raw body, dan hasil parsing.">
        <form method="POST" action="{{ route('debug.openrouter.store') }}" class="space-y-4" data-loading-message="Mengetes endpoint OpenRouter...">
            @csrf
            <div>
                <label for="sample_text" class="block text-sm font-medium">Sample Text</label>
                <x-ui.textarea id="sample_text" name="sample_text" rows="4" required maxlength="5000" class="mt-2">{{ old('sample_text', $sampleText) }}</x-ui.textarea>
                @error('sample_text')
                    <p class="mt-2 text-sm text-[var(--app-danger)]">{{ $message }}</p>
                @enderror
            </div>
            <x-ui.button type="submit">Test OpenRouter</x-ui.button>
        </form>
    </x-ui.card>

    @if ($result)
        <x-ui.card title="Result" class="mt-6">
            <div class="mb-5 flex items-center justify-between gap-4">
                <p class="text-sm text-[var(--app-muted)]">Endpoint response summary</p>
                <x-ui.badge :variant="$result['ok'] ? 'success' : 'danger'">{{ $result['ok'] ? 'OK' : 'FAILED' }}</x-ui.badge>
            </div>

            <dl class="mb-5 grid gap-3 text-sm sm:grid-cols-3">
                <div class="rounded-md border border-[var(--app-border)] bg-[var(--app-surface-strong)] p-3">
                    <dt class="text-[var(--app-muted)]">API Key</dt>
                    <dd class="mt-1 font-medium">{{ $result['configured']['api_key'] ? 'Configured' : 'Missing' }}</dd>
                </div>
                <div class="rounded-md border border-[var(--app-border)] bg-[var(--app-surface-strong)] p-3">
                    <dt class="text-[var(--app-muted)]">Model</dt>
                    <dd class="mt-1 break-all font-medium">{{ $result['configured']['model'] }}</dd>
                </div>
                <div class="rounded-md border border-[var(--app-border)] bg-[var(--app-surface-strong)] p-3">
                    <dt class="text-[var(--app-muted)]">HTTP Status</dt>
                    <dd class="mt-1 font-medium">{{ $result['status'] ?: '-' }}</dd>
                </div>
            </dl>

            @if ($result['error'])
                <x-ui.alert variant="danger" class="mb-5">{{ $result['error'] }}</x-ui.alert>
            @endif

            <div class="grid gap-4 lg:grid-cols-2">
                <div>
                    <h3 class="mb-2 text-sm font-semibold">Parsed</h3>
                    <pre class="app-code overflow-x-auto rounded-md p-4 text-xs leading-6">{{ json_encode($result['parsed'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: 'null' }}</pre>
                </div>
                <div>
                    <h3 class="mb-2 text-sm font-semibold">Raw Body</h3>
                    <pre class="app-code overflow-x-auto rounded-md p-4 text-xs leading-6">{{ is_string($result['body']) ? $result['body'] : json_encode($result['body'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                </div>
            </div>
        </x-ui.card>
    @endif
</x-layouts.app>
