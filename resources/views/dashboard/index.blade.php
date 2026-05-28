@php
    $rupiah = fn ($value) => 'Rp '.number_format((float) ($value ?? 0), 0, ',', '.');
@endphp

<x-layouts.app title="Dashboard CRM">
    <div class="mb-8 flex flex-col justify-between gap-4 md:flex-row md:items-end">
        <div>
            <x-ui.badge variant="info" accent>Realtime parser</x-ui.badge>
            <h1 class="mt-3 text-3xl font-semibold leading-tight">Dashboard CRM</h1>
            <p class="mt-2 max-w-2xl text-sm leading-6 text-[var(--app-muted)]">Input teks manual atau webhook multi-platform, proses langsung dengan OpenRouter, lalu pantau pelanggan, kategori, transaksi, dan total nilai penjualan.</p>
        </div>
        <x-ui.button variant="secondary" :href="route('debug.openrouter')">Debug OpenRouter</x-ui.button>
    </div>

    <section class="mb-6 grid gap-3 sm:grid-cols-2 lg:grid-cols-5">
        <x-ui.card class="lg:col-span-2">
            <div class="flex items-start justify-between gap-3">
                <div>
                    <p class="text-sm text-[var(--app-muted)]">Total Amount</p>
                    <p class="mt-2 text-2xl font-semibold tracking-tight">{{ $rupiah($stats['total_amount']) }}</p>
                    <p class="mt-2 text-xs text-[var(--app-muted)]">Akumulasi dari semua transaksi yang memiliki amount.</p>
                </div>
                <x-ui.badge variant="success" accent>Revenue</x-ui.badge>
            </div>
        </x-ui.card>

        <x-ui.card>
            <p class="text-sm text-[var(--app-muted)]">Total Pelanggan</p>
            <p class="mt-2 text-2xl font-semibold">{{ $stats['customers'] }}</p>
        </x-ui.card>

        <x-ui.card>
            <p class="text-sm text-[var(--app-muted)]">Jumlah Transaksi</p>
            <p class="mt-2 text-2xl font-semibold">{{ $stats['transactions'] }}</p>
        </x-ui.card>

        <x-ui.card>
            <p class="text-sm text-[var(--app-muted)]">Total Kategori</p>
            <p class="mt-2 text-2xl font-semibold">{{ $stats['categories'] }}</p>
        </x-ui.card>
    </section>

    <section class="mb-6 grid gap-3 lg:grid-cols-[1.25fr_2fr]">
        <x-ui.card title="Kategori Terbanyak">
            @if ($stats['top_category'])
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-2xl font-semibold">{{ $stats['top_category'] }}</p>
                        <p class="mt-2 text-sm text-[var(--app-muted)]">{{ $stats['top_category_count'] }} transaksi</p>
                    </div>
                    <x-ui.badge variant="info" accent>Top</x-ui.badge>
                </div>
            @else
                <p class="text-sm text-[var(--app-muted)]">Belum ada kategori.</p>
            @endif
        </x-ui.card>

        <x-ui.card title="Ringkasan Kategori" class="overflow-hidden">
            <div class="-m-5 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="app-table-head text-xs uppercase">
                        <tr>
                            <th class="px-5 py-3">Kategori</th>
                            <th class="px-5 py-3 text-right">Transaksi</th>
                            <th class="px-5 py-3 text-right">Amount</th>
                        </tr>
                    </thead>
                    <tbody class="app-divider">
                        @forelse ($categoryStats as $category)
                            <tr class="hover:bg-[var(--app-surface-strong)]">
                                <td class="px-5 py-4"><x-ui.badge variant="info">{{ $category->category }}</x-ui.badge></td>
                                <td class="px-5 py-4 text-right font-medium">{{ $category->transactions_count }}</td>
                                <td class="px-5 py-4 text-right font-semibold">{{ $rupiah($category->total_amount) }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="3" class="px-5 py-8 text-center text-[var(--app-muted)]">Belum ada kategori.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </section>

    <x-ui.card title="Input Instruksi" description="Masukkan kalimat transaksi, lalu sistem akan langsung mengekstrak platform user ID, produk, kategori, nominal, dan tanggal.">
        <form method="POST" action="{{ route('instructions.store') }}" class="space-y-4" data-loading-message="Memproses pesan dengan OpenRouter...">
            @csrf
            <x-ui.textarea name="raw_text" rows="4" required maxlength="5000" placeholder="Pelanggan 123456789 melakukan pembelian pada Kopi Arabica dengan kategori Minuman harga 100k, atau Hapus semua data">{{ old('raw_text') }}</x-ui.textarea>
            @error('raw_text')
                <p class="text-sm text-[var(--app-danger)]">{{ $message }}</p>
            @enderror
            <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                <p class="text-xs text-[var(--app-muted)]">Hasil akhir muncul setelah request selesai diproses.</p>
                <x-ui.button type="submit">Proses Sekarang</x-ui.button>
            </div>
        </form>

        <div class="my-5 border-t border-[color-mix(in_srgb,var(--app-border)_68%,transparent)]"></div>

        <form method="POST" action="{{ route('instructions.voice.store') }}" enctype="multipart/form-data" class="space-y-4" data-voice-form data-loading-message="Memproses suara dengan OpenRouter...">
            @csrf
            <div>
                <label for="voice" class="block text-sm font-medium">Live Voice Record</label>
                <div class="mt-3 flex flex-col gap-3 sm:flex-row sm:items-center">
                    <x-ui.button type="button" variant="secondary" data-record-start>Mulai Rekam</x-ui.button>
                    <x-ui.button type="button" variant="secondary" data-record-stop disabled>Stop</x-ui.button>
                    <span data-record-status class="text-sm text-[var(--app-muted)]">Belum merekam.</span>
                </div>
                <audio data-record-preview controls class="mt-3 hidden w-full"></audio>
                <input id="voice" name="voice" type="file" required accept="audio/wav,audio/mpeg,audio/mp3,audio/mp4,audio/aac,audio/ogg,audio/webm,audio/flac,.wav,.mp3,.m4a,.aac,.ogg,.webm,.flac" class="app-field mt-4 w-full rounded-md px-3 py-2 text-sm file:mr-4 file:rounded file:border-0 file:bg-[var(--app-primary)] file:px-3 file:py-1.5 file:text-sm file:font-medium file:text-[var(--app-primary-text)]">
                @error('voice')
                    <p class="mt-2 text-sm text-[var(--app-danger)]">{{ $message }}</p>
                @enderror
                <p class="mt-2 text-xs text-[var(--app-muted)]">Gunakan rekam langsung, atau upload fallback. Format: wav, mp3, m4a, ogg, webm, flac, aac. Maksimal 15 MB.</p>
            </div>
            <div class="flex justify-end">
                <x-ui.button type="submit" variant="secondary">Proses Suara</x-ui.button>
            </div>
        </form>
    </x-ui.card>

    <div class="mt-6 grid gap-6 lg:grid-cols-[1fr_1.25fr]">
        <x-ui.card title="Instruksi Terbaru" class="overflow-hidden">
            <div class="-m-5 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="app-table-head text-xs uppercase">
                        <tr>
                            <th class="px-5 py-3">ID</th>
                            <th class="px-5 py-3">Platform</th>
                            <th class="px-5 py-3">User ID</th>
                            <th class="px-5 py-3">Status</th>
                            <th class="px-5 py-3">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="app-divider">
                        @forelse ($instructions as $instruction)
                            @php
                                $statusVariant = match ($instruction->status) {
                                    \App\Models\Instruction::STATUS_SUCCESS => 'success',
                                    \App\Models\Instruction::STATUS_FAILED => 'danger',
                                    \App\Models\Instruction::STATUS_PROCESSING => 'info',
                                    default => 'neutral',
                                };
                            @endphp
                            <tr class="hover:bg-[var(--app-surface-strong)]">
                                <td class="px-5 py-4"><a class="font-semibold text-[var(--app-primary)] underline-offset-4 hover:underline" href="{{ route('instructions.show', $instruction) }}">#{{ $instruction->id }}</a></td>
                                <td class="px-5 py-4"><x-ui.badge variant="neutral">{{ $instruction->platform ?: $instruction->source }}</x-ui.badge></td>
                                <td class="px-5 py-4 font-medium">{{ $instruction->platform_user_id ?: $instruction->telegram_user_id ?: '-' }}</td>
                                <td class="px-5 py-4"><x-ui.badge :variant="$statusVariant">{{ $instruction->status }}</x-ui.badge></td>
                                <td class="px-5 py-4 text-[var(--app-muted)]">{{ $instruction->created_at->format('Y-m-d H:i') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="5" class="px-5 py-8 text-center text-[var(--app-muted)]">Belum ada instruksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>

        <x-ui.card title="Transaksi Terbaru" class="overflow-hidden">
            <div class="-m-5 overflow-x-auto">
                <table class="w-full text-left text-sm">
                    <thead class="app-table-head text-xs uppercase">
                        <tr>
                            <th class="px-5 py-3">Produk</th>
                            <th class="px-5 py-3">Kategori</th>
                            <th class="px-5 py-3">Pelanggan</th>
                            <th class="px-5 py-3">Platform</th>
                            <th class="px-5 py-3 text-right">Amount</th>
                            <th class="px-5 py-3">Tanggal</th>
                        </tr>
                    </thead>
                    <tbody class="app-divider">
                        @forelse ($transactions as $transaction)
                            <tr class="hover:bg-[var(--app-surface-strong)]">
                                <td class="px-5 py-4 font-medium">{{ $transaction->product }}</td>
                                <td class="px-5 py-4"><x-ui.badge variant="info">{{ $transaction->category }}</x-ui.badge></td>
                                <td class="px-5 py-4">{{ $transaction->customer->name ?: $transaction->platform_user_id ?: $transaction->telegram_user_id }}</td>
                                <td class="px-5 py-4"><x-ui.badge variant="neutral">{{ $transaction->platform ?: 'telegram' }}</x-ui.badge></td>
                                <td class="px-5 py-4 text-right font-semibold">{{ $rupiah($transaction->amount) }}</td>
                                <td class="px-5 py-4 text-[var(--app-muted)]">{{ $transaction->transaction_date->format('Y-m-d') }}</td>
                            </tr>
                        @empty
                            <tr><td colspan="6" class="px-5 py-8 text-center text-[var(--app-muted)]">Belum ada transaksi.</td></tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </x-ui.card>
    </div>

    <x-ui.card title="Pelanggan Terbaru" class="mt-6 overflow-hidden">
        <div class="-m-5 overflow-x-auto">
            <table class="w-full text-left text-sm">
                <thead class="app-table-head text-xs uppercase">
                    <tr>
                        <th class="px-5 py-3">Platform</th>
                        <th class="px-5 py-3">User ID</th>
                        <th class="px-5 py-3">Nama</th>
                        <th class="px-5 py-3 text-right">Total Transaksi</th>
                    </tr>
                </thead>
                <tbody class="app-divider">
                    @forelse ($customers as $customer)
                        <tr class="hover:bg-[var(--app-surface-strong)]">
                            <td class="px-5 py-4"><x-ui.badge variant="neutral">{{ $customer->platform ?: 'telegram' }}</x-ui.badge></td>
                            <td class="px-5 py-4 font-medium">{{ $customer->platform_user_id ?: $customer->telegram_user_id }}</td>
                            <td class="px-5 py-4">{{ $customer->name ?: '-' }}</td>
                            <td class="px-5 py-4 text-right"><x-ui.badge variant="success">{{ $customer->transactions_count }}</x-ui.badge></td>
                        </tr>
                    @empty
                        <tr><td colspan="4" class="px-5 py-8 text-center text-[var(--app-muted)]">Belum ada pelanggan.</td></tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </x-ui.card>
</x-layouts.app>
