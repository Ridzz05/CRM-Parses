# CRM Parser

CRM Parser adalah aplikasi Laravel untuk mencatat CRM, transaksi, dan instruksi operasional dari input bahasa natural. Input bisa datang dari dashboard web, Telegram webhook, atau webhook multi-platform seperti WhatsApp Business dan Instagram. Teks atau suara diproses oleh OpenRouter, lalu hasil parsing disimpan sebagai data pelanggan, instruksi, dan transaksi.

## Fitur Utama

- Login admin lokal berbasis session Laravel.
- Dashboard operasional untuk ringkasan transaksi, total amount, pelanggan, kategori, instruksi terbaru, dan transaksi terbaru.
- Input instruksi manual berbasis teks.
- Live voice record dan upload audio untuk membuat transaksi dari suara.
- Webhook Telegram dengan validasi secret header.
- Webhook inbound generik untuk WhatsApp Business, Instagram, Telegram, web, dan platform lain.
- Normalisasi alias platform, misalnya `WA`, `WhatsApp`, dan `WA Business` menjadi `whatsapp_business`, serta `IG` menjadi `instagram`.
- Parser AI via OpenRouter dengan JSON Schema output.
- Global command dari dashboard web, misalnya `Hapus semua data`, untuk reset data CRM.
- Debug page OpenRouter untuk mengetes konfigurasi model dan response parser.
- UI minimalis dengan tema Gruvbox light/dark, theme toggle, snackbar, modal, dan komponen Blade reusable.

## Stack

- Laravel 13
- Blade + Tailwind CSS
- SQLite untuk development lokal
- OpenRouter Chat Completions dan audio input
- PHPUnit untuk test suite
- Vite untuk build asset frontend

## Data Utama

Project ini memakai tiga entitas bisnis utama:

- `customers`: menyimpan identitas pelanggan per platform.
- `instructions`: menyimpan instruksi mentah, sumber input, hasil parsing, status, error, dan waktu proses.
- `transactions`: menyimpan produk, kategori, quantity, amount, tanggal transaksi, dan relasi ke pelanggan serta instruksi.

Kolom `telegram_user_id` masih dipertahankan untuk kompatibilitas awal, tetapi flow baru memakai `platform` dan `platform_user_id` agar bisa mendukung multi-platform.

## Setup Lokal

Install dependency PHP dan JavaScript:

```bash
composer install
npm install
```

Siapkan environment:

```bash
cp .env.example .env
php artisan key:generate
```

Konfigurasi database lokal. Default MVP memakai SQLite:

```env
DB_CONNECTION=sqlite
```

Lalu jalankan migrasi:

```bash
php artisan migrate
```

Buat atau update admin dari env:

```bash
php artisan admin:ensure
```

Jalankan server lokal:

```bash
php artisan serve
npm run dev
```

## Environment Penting

Contoh konfigurasi utama:

```env
ADMIN_NAME="johndoe"
ADMIN_EMAIL="loremdoe@lorem.com"
ADMIN_PASSWORD="change-this-password"

OPENROUTER_API_KEY="your-openrouter-api-key"
OPENROUTER_BASE_URL="https://openrouter.ai/api/v1"
OPENROUTER_MODEL="nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free"
OPENROUTER_AUDIO_MODEL="${OPENROUTER_MODEL}"
OPENROUTER_STT_MODEL="${OPENROUTER_MODEL}"

TELEGRAM_WEBHOOK_SECRET="change-me"
INBOUND_WEBHOOK_SECRET="change-me"
```

Catatan:

- Jangan commit API key atau secret produksi.
- `OPENROUTER_MODEL` dipakai untuk parsing teks.
- `OPENROUTER_AUDIO_MODEL` dipakai untuk input suara via chat/audio model.
- Jika memakai model STT khusus, `OPENROUTER_STT_MODEL` bisa diarahkan ke model transkripsi yang tersedia di OpenRouter.

## Cara Kerja Instruksi

Input contoh:

```text
Pelanggan WA 628123456789 beli Layanan SaaS Lifetime dengan harga 299k
```

OpenRouter diminta mengembalikan JSON terstruktur dengan field minimum:

- `intent`
- `platform`
- `platform_user_id`
- `telegram_user_id`
- `customer_name`
- `product`
- `category`
- `quantity`
- `amount`
- `transaction_date`
- `action_summary`

Laravel tetap memvalidasi payload AI sebelum membuat atau memperbarui customer dan transaction.

Intent yang didukung:

- `transaction`
- `purchase`
- `reset_all`
- `unsupported`

`reset_all` hanya boleh diproses dari dashboard web, bukan webhook eksternal.

## Voice Input

Dashboard menyediakan live voice record melalui browser. Audio dikirim ke endpoint:

```text
POST /instructions/voice
```

Format audio yang diterima:

- `wav`
- `mp3`
- `m4a`
- `ogg`
- `webm`
- `flac`
- `aac`

Untuk model Nvidia Nemotron, audio diproses lewat OpenRouter `/chat/completions` dengan content `input_audio`. Untuk model STT khusus, audio bisa ditranskrip lebih dulu lalu hasil teksnya diproses oleh parser teks.

## Webhook

### Telegram

Endpoint:

```text
POST /api/telegram/webhook
```

Header wajib:

```text
X-Telegram-Bot-Api-Secret-Token: <TELEGRAM_WEBHOOK_SECRET>
```

Endpoint ini membaca `message.text` atau `edited_message.text`, membuat instruction, lalu langsung memprosesnya.

### Inbound Multi-Platform

Endpoint:

```text
POST /api/webhooks/inbound
```

Header wajib:

```text
X-Inbound-Webhook-Secret: <INBOUND_WEBHOOK_SECRET>
```

Payload contoh:

```json
{
  "platform": "WA",
  "platform_user_id": "628123456789",
  "platform_message_id": "msg-001",
  "text": "Pelanggan ini beli Layanan SaaS harga 299k"
}
```

Response akan berisi status instruction, parsed payload, error jika ada, dan transaction jika berhasil dibuat.

## Halaman Aplikasi

- `/login`: halaman login admin.
- `/dashboard`: dashboard CRM dan input instruksi.
- `/instructions/{instruction}`: detail instruksi, raw text, parsed JSON, metadata, error, dan transaksi terkait.
- `/debug/openrouter`: halaman untuk mengetes request OpenRouter dan melihat response mentah.

## UI

UI memakai Blade components reusable:

- `x-ui.button`
- `x-ui.input`
- `x-ui.textarea`
- `x-ui.card`
- `x-ui.badge`
- `x-ui.modal`
- `x-ui.snackbar`
- `x-ui.theme-toggle`

Tema memakai Gruvbox light soft dan dark soft. Font normal tetap dipakai untuk tabel, form, dan deskripsi. Font Playwrite digunakan hanya sebagai label dekoratif pada beberapa aksen UI.

## Testing

Jalankan seluruh test:

```bash
php artisan test
```

Build asset production:

```bash
npm run build
```

Coverage test saat ini mencakup:

- Auth dashboard dan login/logout.
- Input instruksi manual.
- Input suara.
- Parser OpenRouter dengan fake HTTP response.
- Flow audio model Nvidia.
- Telegram webhook secret dan pemrosesan pesan.
- Inbound webhook multi-platform.
- Normalisasi platform alias.
- Job/processor untuk payload valid, payload invalid, dan global reset command.

## Command Berguna

```bash
php artisan admin:ensure
php artisan migrate:fresh
php artisan config:clear
php artisan view:clear
npm run build
php artisan test
```

## Catatan Implementasi

- Processing instruksi berjalan sinkron agar user langsung mendapat hasil akhir dari input dashboard atau webhook.
- Field `status` instruction tetap dipakai untuk audit proses: `processing`, `success`, atau `failed`.
- Error OpenRouter disimpan ke `instructions.error_message` dan ditampilkan di detail instruksi.
- `amount` diformat sebagai Rupiah di UI.
- Nomor transaksi v1 memakai `id` database dan nomor tampilan, belum ada nomor bisnis khusus.
