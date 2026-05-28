<?php

namespace Tests\Unit;

use App\Services\OpenRouterParser;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class OpenRouterParserTest extends TestCase
{
    public function test_parser_returns_decoded_json_content(): void
    {
        config([
            'services.openrouter.key' => 'test-key',
            'services.openrouter.base_url' => 'https://openrouter.ai/api/v1',
            'services.openrouter.model' => 'test/model',
            'services.openrouter.audio_model' => null,
        ]);

        Http::fake([
            'openrouter.ai/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'intent' => 'purchase',
                            'telegram_user_id' => '123',
                            'customer_name' => null,
                            'product' => 'Kopi Arabica',
                            'category' => 'Minuman',
                            'quantity' => 1,
                            'amount' => null,
                            'transaction_date' => '2026-05-28',
                        ]),
                    ],
                ]],
            ]),
        ]);

        $parsed = app(OpenRouterParser::class)->parse('Pelanggan 123 membeli Kopi Arabica kategori Minuman');

        $this->assertSame('123', $parsed['telegram_user_id']);
        $this->assertSame('Kopi Arabica', $parsed['product']);

        Http::assertSent(fn ($request) => $request->hasHeader('Authorization', 'Bearer test-key')
            && $request['model'] === 'test/model'
            && data_get($request->data(), 'response_format.type') === 'json_schema');
    }

    public function test_audio_parser_transcribes_audio_then_uses_text_parser(): void
    {
        config([
            'services.openrouter.key' => 'test-key',
            'services.openrouter.base_url' => 'https://openrouter.ai/api/v1',
            'services.openrouter.model' => 'test/parser-model',
            'services.openrouter.audio_model' => null,
            'services.openrouter.stt_model' => 'test/stt-model',
        ]);

        Http::fake([
            'openrouter.ai/api/v1/audio/transcriptions' => Http::response([
                'text' => 'Pelanggan WA 628123456789 beli Layanan Shopee harga 100k',
            ]),
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
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
                        ]),
                    ],
                ]],
            ]),
        ]);

        $audio = UploadedFile::fake()->create('instruction.webm', 64, 'audio/webm');

        $parsed = app(OpenRouterParser::class)->parseAudio($audio);

        $this->assertSame('whatsapp_business', $parsed['platform']);
        $this->assertSame('Layanan Shopee', $parsed['product']);
        $this->assertSame(100000, $parsed['amount']);

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/audio/transcriptions')
                && $request['model'] === 'test/stt-model'
                && data_get($request->data(), 'input_audio.format') === 'webm';
        });

        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/chat/completions')
                && $request['model'] === 'test/parser-model'
                && data_get($request->data(), 'messages.1.content') === 'Pelanggan WA 628123456789 beli Layanan Shopee harga 100k';
        });
    }

    public function test_nvidia_audio_model_uses_chat_audio_instead_of_stt_endpoint(): void
    {
        config([
            'services.openrouter.key' => 'test-key',
            'services.openrouter.base_url' => 'https://openrouter.ai/api/v1',
            'services.openrouter.model' => 'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free',
            'services.openrouter.audio_model' => 'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free',
            'services.openrouter.stt_model' => 'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free',
        ]);

        Http::fake([
            'openrouter.ai/api/v1/chat/completions' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
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
                        ]),
                    ],
                ]],
            ]),
        ]);

        $audio = UploadedFile::fake()->create('instruction.webm', 64, 'audio/webm');

        $parsed = app(OpenRouterParser::class)->parseAudio($audio);

        $this->assertSame('whatsapp_business', $parsed['platform']);
        $this->assertSame('Layanan Shopee', $parsed['product']);

        Http::assertSentCount(1);
        Http::assertSent(function ($request) {
            return str_ends_with($request->url(), '/chat/completions')
                && $request['model'] === 'nvidia/nemotron-3-nano-omni-30b-a3b-reasoning:free'
                && data_get($request->data(), 'messages.1.content.1.type') === 'input_audio'
                && data_get($request->data(), 'messages.1.content.1.input_audio.format') === 'webm';
        });
    }
}
