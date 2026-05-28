<?php

namespace App\Http\Controllers;

use App\Models\Instruction;
use App\Services\InstructionProcessor;
use App\Support\Platform;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class InboundWebhookController extends Controller
{
    public function __invoke(Request $request, InstructionProcessor $processor): JsonResponse
    {
        $secret = config('services.inbound_webhook.secret');

        if (! $secret) {
            return response()->json(['message' => 'Inbound webhook secret is not configured.'], 503);
        }

        if (! hash_equals((string) $secret, (string) $request->header('X-Inbound-Webhook-Secret'))) {
            return response()->json(['message' => 'Unauthorized'], 401);
        }

        $data = $request->validate([
            'platform' => ['required', 'string', 'max:255'],
            'platform_user_id' => ['required', 'string', 'max:255'],
            'platform_message_id' => ['nullable', 'string', 'max:255'],
            'text' => ['required', 'string', 'max:5000'],
        ]);

        $platform = Platform::normalize($data['platform']);

        $instruction = Instruction::create([
            'source' => $platform,
            'platform' => $platform,
            'platform_user_id' => $data['platform_user_id'],
            'platform_message_id' => $data['platform_message_id'] ?? null,
            'external_message_id' => $data['platform_message_id'] ?? null,
            'telegram_user_id' => $platform === 'telegram' ? $data['platform_user_id'] : null,
            'raw_text' => $data['text'],
            'status' => Instruction::STATUS_PROCESSING,
        ]);

        $instruction = $processor->process($instruction);

        return response()->json([
            'message' => $instruction->status === Instruction::STATUS_SUCCESS ? 'Processed' : 'Failed',
            'instruction_id' => $instruction->id,
            'status' => $instruction->status,
            'platform' => $instruction->platform,
            'platform_user_id' => $instruction->platform_user_id,
            'error_message' => $instruction->error_message,
            'parsed_payload' => $instruction->parsed_payload,
            'transaction' => $instruction->transaction,
        ], $instruction->status === Instruction::STATUS_SUCCESS ? 200 : 422);
    }
}
