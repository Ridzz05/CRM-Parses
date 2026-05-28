<?php

namespace Tests\Feature;

use App\Models\Instruction;
use App\Models\Transaction;
use App\Services\OpenRouterParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TelegramWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_rejects_invalid_secret(): void
    {
        config(['services.telegram.webhook_secret' => 'secret-value']);

        $this->postJson('/api/telegram/webhook', [])
            ->assertUnauthorized();
    }

    public function test_webhook_creates_instruction_for_valid_text_message(): void
    {
        config(['services.telegram.webhook_secret' => 'secret-value']);
        $this->app->bind(OpenRouterParser::class, fn () => new class extends OpenRouterParser {
            public function parse(string $rawText): array
            {
                return [
                    'intent' => 'purchase',
                    'telegram_user_id' => '123456789',
                    'customer_name' => null,
                    'product' => 'Kopi Arabica',
                    'category' => 'Minuman',
                    'quantity' => null,
                    'amount' => null,
                    'transaction_date' => '2026-05-28',
                ];
            }
        });

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret-value')
            ->postJson('/api/telegram/webhook', [
                'message' => [
                    'message_id' => 77,
                    'from' => ['id' => 123456789],
                    'text' => 'Pelanggan 123456789 membeli Kopi Arabica kategori Minuman',
                ],
            ])
            ->assertOk()
            ->assertJsonPath('message', 'Processed')
            ->assertJsonPath('status', Instruction::STATUS_SUCCESS);

        $instruction = Instruction::firstOrFail();

        $this->assertSame('telegram', $instruction->source);
        $this->assertSame('77', $instruction->external_message_id);
        $this->assertSame('123456789', $instruction->telegram_user_id);

        $this->assertDatabaseHas(Transaction::class, [
            'instruction_id' => $instruction->id,
            'product' => 'Kopi Arabica',
        ]);
    }

    public function test_telegram_webhook_cannot_reset_all_data(): void
    {
        config(['services.telegram.webhook_secret' => 'secret-value']);
        $this->app->bind(OpenRouterParser::class, fn () => new class extends OpenRouterParser {
            public function parse(string $rawText): array
            {
                return [
                    'intent' => 'reset_all',
                    'telegram_user_id' => null,
                    'customer_name' => null,
                    'product' => null,
                    'category' => null,
                    'quantity' => null,
                    'amount' => null,
                    'transaction_date' => null,
                    'action_summary' => 'Hapus semua data CRM.',
                ];
            }
        });

        $this->withHeader('X-Telegram-Bot-Api-Secret-Token', 'secret-value')
            ->postJson('/api/telegram/webhook', [
                'message' => [
                    'message_id' => 78,
                    'from' => ['id' => 123456789],
                    'text' => 'Hapus semua data',
                ],
            ])
            ->assertUnprocessable()
            ->assertJsonPath('status', Instruction::STATUS_FAILED);

        $this->assertSame(Instruction::STATUS_FAILED, Instruction::firstOrFail()->status);
    }
}
