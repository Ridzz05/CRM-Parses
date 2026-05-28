<x-layouts.app title="Instruksi #{{ $instruction->id }}">
    @php
        $statusVariant = match ($instruction->status) {
            \App\Models\Instruction::STATUS_SUCCESS => 'success',
            \App\Models\Instruction::STATUS_FAILED => 'danger',
            \App\Models\Instruction::STATUS_PROCESSING => 'info',
            default => 'neutral',
        };
    @endphp

    <div class="mb-6 flex flex-col justify-between gap-4 md:flex-row md:items-center">
        <div>
            <x-ui.button variant="ghost" :href="route('dashboard')" class="-ml-4">Kembali ke dashboard</x-ui.button>
            <div class="mt-2 flex flex-wrap items-center gap-3">
                <h1 class="text-3xl font-semibold">Instruksi #{{ $instruction->id }}</h1>
                <x-ui.badge :variant="$statusVariant">{{ $instruction->status }}</x-ui.badge>
            </div>
            <p class="mt-2 text-sm text-[var(--app-muted)]">Detail instruksi, payload AI, metadata platform, dan transaksi atau global command tertaut.</p>
        </div>

        @if ($instruction->status === \App\Models\Instruction::STATUS_FAILED)
            <x-ui.button type="button" onclick="document.getElementById('retry-instruction-modal').showModal()">Retry</x-ui.button>
        @endif
    </div>

    <div class="grid gap-6 lg:grid-cols-2">
        <x-ui.card title="Raw Text">
            <p class="whitespace-pre-wrap text-sm leading-7 text-[var(--app-text)]">{{ $instruction->raw_text }}</p>
        </x-ui.card>

        <x-ui.card title="Parsed Payload">
            <pre class="app-code overflow-x-auto rounded-md p-4 text-xs leading-6">{{ json_encode($instruction->parsed_payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) ?: '-' }}</pre>
        </x-ui.card>

        <x-ui.card title="Metadata">
            <dl class="space-y-4 text-sm">
                <div class="flex justify-between gap-4"><dt class="text-[var(--app-muted)]">Source</dt><dd class="font-medium">{{ $instruction->source }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-[var(--app-muted)]">Platform</dt><dd class="font-medium">{{ $instruction->platform ?: '-' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-[var(--app-muted)]">Platform User ID</dt><dd class="font-medium">{{ $instruction->platform_user_id ?: $instruction->telegram_user_id ?: '-' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-[var(--app-muted)]">Platform Message</dt><dd class="font-medium">{{ $instruction->platform_message_id ?: $instruction->external_message_id ?: '-' }}</dd></div>
                <div class="flex justify-between gap-4"><dt class="text-[var(--app-muted)]">Processed</dt><dd class="font-medium">{{ $instruction->processed_at?->format('Y-m-d H:i') ?: '-' }}</dd></div>
            </dl>
            @if ($instruction->error_message)
                <x-ui.alert variant="danger" class="mt-5">{{ $instruction->error_message }}</x-ui.alert>
            @endif
        </x-ui.card>

        <x-ui.card title="Result">
            @if (($instruction->parsed_payload['intent'] ?? null) === 'reset_all')
                <div class="space-y-4 text-sm">
                    <x-ui.alert variant="success">Global command selesai dijalankan.</x-ui.alert>
                    <dl class="space-y-4">
                        <div class="flex justify-between gap-4"><dt class="text-[var(--app-muted)]">Action</dt><dd class="font-medium">Reset semua data CRM</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-[var(--app-muted)]">Transaksi dihapus</dt><dd class="font-medium">{{ $instruction->parsed_payload['deleted']['transactions'] ?? 0 }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-[var(--app-muted)]">Pelanggan dihapus</dt><dd class="font-medium">{{ $instruction->parsed_payload['deleted']['customers'] ?? 0 }}</dd></div>
                        <div class="flex justify-between gap-4"><dt class="text-[var(--app-muted)]">Instruksi lama dihapus</dt><dd class="font-medium">{{ $instruction->parsed_payload['deleted']['instructions'] ?? 0 }}</dd></div>
                    </dl>
                </div>
            @elseif ($instruction->transaction)
                <dl class="space-y-4 text-sm">
                    <div class="flex justify-between gap-4"><dt class="text-[var(--app-muted)]">Produk</dt><dd class="font-medium">{{ $instruction->transaction->product }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-[var(--app-muted)]">Kategori</dt><dd><x-ui.badge variant="info">{{ $instruction->transaction->category }}</x-ui.badge></dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-[var(--app-muted)]">Pelanggan</dt><dd class="font-medium">{{ $instruction->transaction->customer->name ?: $instruction->transaction->platform_user_id ?: $instruction->transaction->telegram_user_id }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-[var(--app-muted)]">Platform</dt><dd class="font-medium">{{ $instruction->transaction->platform ?: 'telegram' }}</dd></div>
                    <div class="flex justify-between gap-4"><dt class="text-[var(--app-muted)]">Tanggal</dt><dd class="font-medium">{{ $instruction->transaction->transaction_date->format('Y-m-d') }}</dd></div>
                    @if ($instruction->transaction->amount)
                        <div class="flex justify-between gap-4"><dt class="text-[var(--app-muted)]">Amount</dt><dd class="font-medium">{{ number_format((float) $instruction->transaction->amount, 0, ',', '.') }}</dd></div>
                    @endif
                </dl>
            @else
                <p class="text-sm text-[var(--app-muted)]">Belum ada hasil transaksi atau global command tertaut.</p>
            @endif
        </x-ui.card>
    </div>

    @if ($instruction->status === \App\Models\Instruction::STATUS_FAILED)
        <x-ui.modal id="retry-instruction-modal" title="Retry instruksi">
            <p class="text-sm leading-6 text-[var(--app-muted)]">Instruksi ini akan diproses ulang dengan konfigurasi OpenRouter saat ini.</p>
            <div class="mt-5 flex justify-end gap-3">
                <x-ui.button variant="secondary" type="button" onclick="document.getElementById('retry-instruction-modal').close()">Batal</x-ui.button>
                <form method="POST" action="{{ route('instructions.retry', $instruction) }}" data-loading-message="Memproses ulang instruksi...">
                    @csrf
                    <x-ui.button type="submit">Retry</x-ui.button>
                </form>
            </div>
        </x-ui.modal>
    @endif
</x-layouts.app>
