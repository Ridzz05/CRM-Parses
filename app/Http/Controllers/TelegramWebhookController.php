<?php

namespace App\Http\Controllers;

use App\Models\Instruction;
use App\Services\InstructionProcessor;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TelegramWebhookController extends Controller
{
    public function __invoke(Request $request, InstructionProcessor $processor): JsonResponse
    {
        $secret = config('services.telegram.webhook_secret');

        if (! $secret) {
            return response()->json(['message' => 'Telegram webhook secret is not configured.'], 503);
        }

        if (! hash_equals((string) $secret, (string) $request->header('X-Telegram-Bot-Api-Secret-Token'))) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $message = $request->input('message') ?? $request->input('edited_message') ?? [];
        $text = data_get($message, 'text');

        if (! is_string($text) || trim($text) === '') {
            return response()->json(['message' => 'No text message to process.'], 422);
        }

        $instruction = Instruction::create([
            'source' => 'telegram',
            'platform' => 'telegram',
            'platform_user_id' => data_get($message, 'from.id') ? (string) data_get($message, 'from.id') : null,
            'platform_message_id' => (string) data_get($message, 'message_id'),
            'external_message_id' => (string) data_get($message, 'message_id'),
            'telegram_user_id' => data_get($message, 'from.id') ? (string) data_get($message, 'from.id') : null,
            'raw_text' => $text,
            'status' => Instruction::STATUS_PROCESSING,
        ]);

        $instruction = $processor->process($instruction);

        return response()->json([
            'message' => $instruction->status === Instruction::STATUS_SUCCESS ? 'Processed' : 'Failed',
            'instruction_id' => $instruction->id,
            'status' => $instruction->status,
            'error_message' => $instruction->error_message,
            'parsed_payload' => $instruction->parsed_payload,
            'transaction' => $instruction->transaction,
        ], $instruction->status === Instruction::STATUS_SUCCESS ? 200 : 422);
    }
}
