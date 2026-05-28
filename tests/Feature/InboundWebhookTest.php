<?php

namespace Tests\Feature;

use App\Models\Instruction;
use App\Models\Transaction;
use App\Services\OpenRouterParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InboundWebhookTest extends TestCase
{
    use RefreshDatabase;

    public function test_inbound_webhook_processes_whatsapp_business_payload(): void
    {
        config(['services.inbound_webhook.secret' => 'secret-value']);

        $this->app->bind(OpenRouterParser::class, fn () => new class extends OpenRouterParser {
            public function parse(string $rawText): array
            {
                return [
                    'intent' => 'purchase',
                    'platform' => 'whatsapp_business',
                    'platform_user_id' => '628123456789',
                    'telegram_user_id' => null,
                    'customer_name' => null,
                    'product' => 'Layanan Shopee',
                    'category' => 'Layanan',
                    'quantity' => 1,
                    'amount' => 100000,
                    'transaction_date' => '2026-05-28',
                    'action_summary' => null,
                ];
            }
        });

        $this->withHeader('X-Inbound-Webhook-Secret', 'secret-value')
            ->postJson('/api/webhooks/inbound', [
                'platform' => 'whatsapp_business',
                'platform_user_id' => '628123456789',
                'platform_message_id' => 'wamid.123',
                'text' => 'Pelanggan 628123456789 beli Layanan Shopee harga 100k',
            ])
            ->assertOk()
            ->assertJsonPath('platform', 'whatsapp_business')
            ->assertJsonPath('platform_user_id', '628123456789');

        $instruction = Instruction::firstOrFail();

        $this->assertSame('whatsapp_business', $instruction->platform);
        $this->assertSame('628123456789', $instruction->platform_user_id);
        $this->assertSame('wamid.123', $instruction->platform_message_id);

        $this->assertDatabaseHas(Transaction::class, [
            'instruction_id' => $instruction->id,
            'platform' => 'whatsapp_business',
            'platform_user_id' => '628123456789',
            'product' => 'Layanan Shopee',
        ]);
    }

    public function test_inbound_webhook_normalizes_platform_aliases(): void
    {
        config(['services.inbound_webhook.secret' => 'secret-value']);

        $this->app->bind(OpenRouterParser::class, fn () => new class extends OpenRouterParser {
            public function parse(string $rawText): array
            {
                return [
                    'intent' => 'purchase',
                    'platform' => 'WA',
                    'platform_user_id' => '628987654321',
                    'telegram_user_id' => null,
                    'customer_name' => null,
                    'product' => 'Layanan IG',
                    'category' => 'Layanan',
                    'quantity' => 1,
                    'amount' => 75000,
                    'transaction_date' => '2026-05-28',
                    'action_summary' => null,
                ];
            }
        });

        $this->withHeader('X-Inbound-Webhook-Secret', 'secret-value')
            ->postJson('/api/webhooks/inbound', [
                'platform' => 'WA',
                'platform_user_id' => '628987654321',
                'platform_message_id' => 'wamid.456',
                'text' => 'WA 628987654321 beli Layanan IG harga 75k',
            ])
            ->assertOk()
            ->assertJsonPath('platform', 'whatsapp_business');

        $this->assertDatabaseHas(Transaction::class, [
            'platform' => 'whatsapp_business',
            'platform_user_id' => '628987654321',
        ]);
    }
}
