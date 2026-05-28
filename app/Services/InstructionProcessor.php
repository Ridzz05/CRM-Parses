<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Instruction;
use App\Models\Transaction;
use App\Support\Platform;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;
use RuntimeException;
use Throwable;

class InstructionProcessor
{
    public function __construct(private readonly OpenRouterParser $parser)
    {
    }

    public function process(Instruction $instruction): Instruction
    {
        return $this->processWithPayload($instruction, fn (): array => $this->parser->parse($instruction->raw_text));
    }

    public function processAudio(Instruction $instruction, UploadedFile $audio): Instruction
    {
        return $this->processWithPayload($instruction, fn (): array => $this->parser->parseAudio($audio));
    }

    /**
     * @param  callable(): array<string, mixed>  $payloadResolver
     */
    private function processWithPayload(Instruction $instruction, callable $payloadResolver): Instruction
    {
        $instruction->update([
            'status' => Instruction::STATUS_PROCESSING,
            'error_message' => null,
            'processed_at' => null,
        ]);

        try {
            $payload = $payloadResolver();
            $validated = $this->validatePayload($payload);

            if ($validated['intent'] === 'reset_all') {
                $this->resetAllData($instruction, $validated);

                return $instruction->fresh(['transaction.customer']);
            }

            if ($validated['intent'] === 'unsupported') {
                throw new RuntimeException('Instruksi tidak dikenali sebagai transaksi atau global command yang didukung.');
            }

            $platform = $this->platformFor($instruction, $validated);
            $platformUserId = (string) (($validated['platform_user_id'] ?? null) ?: ($validated['telegram_user_id'] ?? null));

            $customer = Customer::updateOrCreate(
                [
                    'platform' => $platform,
                    'platform_user_id' => $platformUserId,
                ],
                [
                    'telegram_user_id' => $platform === 'telegram' ? $platformUserId : $platform.':'.$platformUserId,
                    'name' => $validated['customer_name'] ?: null,
                ],
            );

            Transaction::updateOrCreate(
                ['instruction_id' => $instruction->id],
                [
                    'customer_id' => $customer->id,
                    'platform' => $platform,
                    'platform_user_id' => $platformUserId,
                    'telegram_user_id' => $platform === 'telegram' ? $platformUserId : $platform.':'.$platformUserId,
                    'product' => $validated['product'],
                    'category' => $validated['category'],
                    'quantity' => $validated['quantity'],
                    'amount' => $validated['amount'],
                    'transaction_date' => $validated['transaction_date'] ?: Carbon::today()->toDateString(),
                    'raw_text' => $instruction->raw_text,
                ],
            );

            $instruction->update([
                'platform' => $platform,
                'platform_user_id' => $platformUserId,
                'telegram_user_id' => $platform === 'telegram' ? $platformUserId : null,
                'parsed_payload' => $validated,
                'status' => Instruction::STATUS_SUCCESS,
                'error_message' => null,
                'processed_at' => now(),
            ]);
        } catch (Throwable $exception) {
            Log::error('Instruction processing failed', [
                'instruction_id' => $instruction->id,
                'source' => $instruction->source,
                'exception' => $exception::class,
                'message' => $exception->getMessage(),
            ]);

            $instruction->update([
                'status' => Instruction::STATUS_FAILED,
                'error_message' => $exception->getMessage(),
                'processed_at' => now(),
            ]);
        }

        return $instruction->fresh(['transaction.customer']);
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    private function validatePayload(array $payload): array
    {
        $validator = Validator::make($payload, [
            'intent' => ['required', 'string', 'in:transaction,purchase,reset_all,unsupported'],
            'platform' => ['nullable', 'string', 'max:255'],
            'platform_user_id' => ['nullable', 'string', 'max:255'],
            'telegram_user_id' => ['nullable', 'string', 'max:255'],
            'customer_name' => ['nullable', 'string', 'max:255'],
            'product' => ['nullable', 'required_if:intent,transaction,purchase', 'string', 'max:255'],
            'category' => ['nullable', 'required_if:intent,transaction,purchase', 'string', 'max:255'],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'amount' => ['nullable', 'numeric', 'min:0'],
            'transaction_date' => ['nullable', 'date_format:Y-m-d'],
            'action_summary' => ['nullable', 'string', 'max:500'],
        ]);

        $validator->after(function ($validator) use ($payload): void {
            if (in_array($payload['intent'] ?? null, ['transaction', 'purchase'], true)
                && blank($payload['platform_user_id'] ?? null)
                && blank($payload['telegram_user_id'] ?? null)) {
                $validator->errors()->add('platform_user_id', 'Platform user ID is required for transaction instructions.');
            }
        });

        return $validator->validate();
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resetAllData(Instruction $instruction, array $payload): void
    {
        if ($instruction->source !== 'web') {
            throw new RuntimeException('Global command reset data hanya boleh dijalankan dari dashboard web.');
        }

        $deleted = [
            'transactions' => Transaction::count(),
            'customers' => Customer::count(),
            'instructions' => Instruction::whereKeyNot($instruction->id)->count(),
        ];

        Transaction::query()->delete();
        Customer::query()->delete();
        Instruction::whereKeyNot($instruction->id)->delete();

        $instruction->update([
            'telegram_user_id' => null,
            'platform_user_id' => null,
            'parsed_payload' => array_merge($payload, [
                'deleted' => $deleted,
            ]),
            'status' => Instruction::STATUS_SUCCESS,
            'error_message' => null,
            'processed_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function platformFor(Instruction $instruction, array $payload): string
    {
        if (blank($payload['platform'] ?? null) && filled($payload['telegram_user_id'] ?? null)) {
            return 'telegram';
        }

        $platform = ($payload['platform'] ?? null) ?: $instruction->platform ?: $instruction->source ?: 'web';

        return Platform::normalize($platform);
    }
}
