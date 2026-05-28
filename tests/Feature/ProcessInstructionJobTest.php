<?php

namespace Tests\Feature;

use App\Jobs\ProcessInstructionJob;
use App\Models\Customer;
use App\Models\Instruction;
use App\Models\Transaction;
use App\Services\OpenRouterParser;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProcessInstructionJobTest extends TestCase
{
    use RefreshDatabase;

    public function test_job_creates_customer_and_transaction_from_valid_ai_payload(): void
    {
        $instruction = Instruction::create([
            'source' => 'web',
            'raw_text' => 'Pelanggan 123 membeli Kopi Arabica kategori Minuman',
            'status' => Instruction::STATUS_PENDING,
        ]);

        $this->app->bind(OpenRouterParser::class, fn () => new class extends OpenRouterParser {
            public function parse(string $rawText): array
            {
                return [
                    'intent' => 'purchase',
                    'telegram_user_id' => '123',
                    'customer_name' => 'Rizki',
                    'product' => 'Kopi Arabica',
                    'category' => 'Minuman',
                    'quantity' => 2,
                    'amount' => 50000,
                    'transaction_date' => '2026-05-28',
                ];
            }
        });

        app(ProcessInstructionJob::class, ['instructionId' => $instruction->id])->handle(app(OpenRouterParser::class));

        $instruction->refresh();

        $this->assertSame(Instruction::STATUS_SUCCESS, $instruction->status);
        $this->assertSame('123', $instruction->telegram_user_id);
        $this->assertDatabaseHas(Customer::class, [
            'telegram_user_id' => '123',
            'name' => 'Rizki',
        ]);
        $this->assertDatabaseHas(Transaction::class, [
            'telegram_user_id' => '123',
            'product' => 'Kopi Arabica',
            'category' => 'Minuman',
        ]);
    }

    public function test_job_marks_instruction_failed_for_invalid_payload(): void
    {
        $instruction = Instruction::create([
            'source' => 'web',
            'raw_text' => 'data tidak lengkap',
            'status' => Instruction::STATUS_PENDING,
        ]);

        $this->app->bind(OpenRouterParser::class, fn () => new class extends OpenRouterParser {
            public function parse(string $rawText): array
            {
                return ['intent' => 'purchase'];
            }
        });

        app(ProcessInstructionJob::class, ['instructionId' => $instruction->id])->handle(app(OpenRouterParser::class));

        $instruction->refresh();

        $this->assertSame(Instruction::STATUS_FAILED, $instruction->status);
        $this->assertNotNull($instruction->error_message);
        $this->assertDatabaseCount('transactions', 0);
    }

    public function test_web_instruction_can_reset_all_crm_data(): void
    {
        $customer = Customer::create([
            'telegram_user_id' => '123',
            'name' => 'Rizki',
        ]);
        $oldInstruction = Instruction::create([
            'source' => 'web',
            'raw_text' => 'old',
            'status' => Instruction::STATUS_SUCCESS,
        ]);
        Transaction::create([
            'customer_id' => $customer->id,
            'telegram_user_id' => '123',
            'product' => 'Kopi',
            'category' => 'Minuman',
            'quantity' => 1,
            'amount' => 10000,
            'transaction_date' => '2026-05-28',
            'raw_text' => 'old',
            'instruction_id' => $oldInstruction->id,
        ]);
        $resetInstruction = Instruction::create([
            'source' => 'web',
            'raw_text' => 'Hapus semua data',
            'status' => Instruction::STATUS_PENDING,
        ]);

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

        app(ProcessInstructionJob::class, ['instructionId' => $resetInstruction->id])->handle(app(OpenRouterParser::class));

        $resetInstruction->refresh();

        $this->assertSame(Instruction::STATUS_SUCCESS, $resetInstruction->status);
        $this->assertDatabaseCount('transactions', 0);
        $this->assertDatabaseCount('customers', 0);
        $this->assertDatabaseCount('instructions', 1);
        $this->assertSame(1, $resetInstruction->parsed_payload['deleted']['transactions']);
    }
}
