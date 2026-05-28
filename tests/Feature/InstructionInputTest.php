<?php

namespace Tests\Feature;

use App\Models\Instruction;
use App\Models\Transaction;
use App\Models\User;
use App\Services\OpenRouterParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class InstructionInputTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->withoutVite();
    }

    public function test_manual_input_processes_instruction_immediately(): void
    {
        $this->app->bind(OpenRouterParser::class, fn () => new class extends OpenRouterParser {
            public function parse(string $rawText): array
            {
                return [
                    'intent' => 'purchase',
                    'telegram_user_id' => '123',
                    'customer_name' => null,
                    'product' => 'Kopi Arabica',
                    'category' => 'Minuman',
                    'quantity' => null,
                    'amount' => null,
                    'transaction_date' => '2026-05-28',
                ];
            }
        });

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/instructions', [
                'raw_text' => 'Pelanggan 123 membeli Kopi Arabica kategori Minuman',
            ])
            ->assertRedirect();

        $instruction = Instruction::firstOrFail();

        $this->assertSame('web', $instruction->source);
        $this->assertSame(Instruction::STATUS_SUCCESS, $instruction->status);

        $this->assertDatabaseHas(Transaction::class, [
            'instruction_id' => $instruction->id,
            'product' => 'Kopi Arabica',
            'category' => 'Minuman',
        ]);
    }

    public function test_voice_input_processes_instruction_immediately(): void
    {
        $this->app->bind(OpenRouterParser::class, fn () => new class extends OpenRouterParser {
            public function parseAudio(UploadedFile $audio): array
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

        $user = User::factory()->create();

        $this->actingAs($user)
            ->post('/instructions/voice', [
                'voice' => UploadedFile::fake()->create('instruction.mp3', 64, 'audio/mpeg'),
            ])
            ->assertRedirect();

        $instruction = Instruction::firstOrFail();

        $this->assertSame('web_voice', $instruction->source);
        $this->assertSame(Instruction::STATUS_SUCCESS, $instruction->status);

        $this->assertDatabaseHas(Transaction::class, [
            'instruction_id' => $instruction->id,
            'platform' => 'whatsapp_business',
            'platform_user_id' => '628123456789',
            'product' => 'Layanan Shopee',
        ]);
    }
}
