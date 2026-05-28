<?php

namespace App\Services;

use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Http;
use RuntimeException;

class OpenRouterParser
{
    /**
     * @return array<string, mixed>
     */
    public function parse(string $rawText): array
    {
        $apiKey = config('services.openrouter.key');

        if (! $apiKey) {
            throw new RuntimeException('OPENROUTER_API_KEY is not configured.');
        }

        try {
            $response = $this->client()
                ->post('/chat/completions', $this->payload($rawText))
                ->throw()
                ->json();
        } catch (ConnectionException $exception) {
            throw new RuntimeException('OpenRouter connection failed: '.$exception->getMessage(), previous: $exception);
        } catch (RequestException $exception) {
            $status = $exception->response->status();
            $body = str($exception->response->body())->limit(500)->toString();

            throw new RuntimeException("OpenRouter HTTP {$status}: {$body}", previous: $exception);
        }

        $content = data_get($response, 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('OpenRouter returned an empty parser response.');
        }

        $parsed = json_decode($content, true);

        if (! is_array($parsed)) {
            throw new RuntimeException('OpenRouter returned invalid JSON.');
        }

        return $parsed;
    }

    /**
     * @return array<string, mixed>
     */
    public function parseAudio(UploadedFile $audio): array
    {
        if ($this->usesChatAudioModel()) {
            return $this->parseAudioWithChatModel($audio);
        }

        $transcript = $this->transcribeAudio($audio);

        return $this->parse($transcript);
    }

    /**
     * @return array<string, mixed>
     */
    protected function parseAudioWithChatModel(UploadedFile $audio): array
    {
        $apiKey = config('services.openrouter.key');

        if (! $apiKey) {
            throw new RuntimeException('OPENROUTER_API_KEY is not configured.');
        }

        try {
            $response = $this->client()
                ->timeout(60)
                ->post('/chat/completions', $this->audioPayload($audio))
                ->throw()
                ->json();
        } catch (ConnectionException $exception) {
            throw new RuntimeException('OpenRouter audio connection failed: '.$exception->getMessage(), previous: $exception);
        } catch (RequestException $exception) {
            $status = $exception->response->status();
            $body = str($exception->response->body())->limit(500)->toString();

            throw new RuntimeException("OpenRouter audio HTTP {$status}: {$body}", previous: $exception);
        }

        $content = data_get($response, 'choices.0.message.content');

        if (! is_string($content) || trim($content) === '') {
            throw new RuntimeException('OpenRouter returned an empty audio parser response.');
        }

        $parsed = json_decode($content, true);

        if (! is_array($parsed)) {
            throw new RuntimeException('OpenRouter returned invalid audio parser JSON.');
        }

        return $parsed;
    }

    public function transcribeAudio(UploadedFile $audio): string
    {
        $apiKey = config('services.openrouter.key');

        if (! $apiKey) {
            throw new RuntimeException('OPENROUTER_API_KEY is not configured.');
        }

        $format = $this->audioFormat($audio);
        $base64Audio = base64_encode((string) file_get_contents($audio->getRealPath()));

        try {
            $response = $this->client()
                ->timeout(60)
                ->post('/audio/transcriptions', [
                    'model' => config('services.openrouter.stt_model'),
                    'input_audio' => [
                        'data' => $base64Audio,
                        'format' => $format,
                    ],
                    'language' => 'id',
                ])
                ->throw()
                ->json();
        } catch (ConnectionException $exception) {
            throw new RuntimeException('OpenRouter STT connection failed: '.$exception->getMessage(), previous: $exception);
        } catch (RequestException $exception) {
            $status = $exception->response->status();
            $body = str($exception->response->body())->limit(500)->toString();

            throw new RuntimeException("OpenRouter STT HTTP {$status}: {$body}", previous: $exception);
        }

        $text = data_get($response, 'text');

        if (! is_string($text) || trim($text) === '') {
            throw new RuntimeException('OpenRouter STT returned an empty transcription.');
        }

        return trim($text);
    }

    /**
     * @return array<string, mixed>
     */
    public function diagnostics(string $sampleText): array
    {
        $apiKey = config('services.openrouter.key');

        $result = [
            'configured' => [
                'api_key' => filled($apiKey),
                'base_url' => config('services.openrouter.base_url'),
                'model' => config('services.openrouter.model'),
            ],
            'ok' => false,
            'status' => null,
            'body' => null,
            'parsed' => null,
            'error' => null,
        ];

        if (! $apiKey) {
            $result['error'] = 'OPENROUTER_API_KEY is not configured.';

            return $result;
        }

        try {
            $response = $this->client()->post('/chat/completions', $this->payload($sampleText));

            $result['status'] = $response->status();
            $result['body'] = $response->json() ?? $response->body();
            $result['ok'] = $response->successful();

            if ($response->successful()) {
                $content = data_get($response->json(), 'choices.0.message.content');
                $result['parsed'] = is_string($content) ? json_decode($content, true) : null;
            }
        } catch (ConnectionException $exception) {
            $result['error'] = 'OpenRouter connection failed: '.$exception->getMessage();
        }

        return $result;
    }

    protected function client(): PendingRequest
    {
        return Http::baseUrl(rtrim((string) config('services.openrouter.base_url'), '/'))
            ->withToken((string) config('services.openrouter.key'))
            ->acceptJson()
            ->asJson()
            ->timeout(30)
            ->retry(2, 250);
    }

    protected function usesChatAudioModel(): bool
    {
        $audioModel = (string) config('services.openrouter.audio_model');

        return filled($audioModel)
            && (blank(config('services.openrouter.stt_model')) || str_starts_with($audioModel, 'nvidia/nemotron-3-'));
    }

    /**
     * @return array<string, mixed>
     */
    protected function audioPayload(UploadedFile $audio): array
    {
        $format = $this->audioFormat($audio);
        $base64Audio = base64_encode((string) file_get_contents($audio->getRealPath()));

        return [
            'model' => config('services.openrouter.audio_model'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Transcribe the Indonesian audio instruction, then classify it as CRM data. For purchases or transactions, extract platform identity and transaction fields. For global commands like "hapus semua data", return intent reset_all and leave transaction fields null. Return unsupported when the instruction is not actionable. Use null when values are absent. Return JSON only.',
                ],
                [
                    'role' => 'user',
                    'content' => [
                        [
                            'type' => 'text',
                            'text' => 'Parse this voice instruction into the required CRM JSON schema.',
                        ],
                        [
                            'type' => 'input_audio',
                            'input_audio' => [
                                'data' => $base64Audio,
                                'format' => $format,
                            ],
                        ],
                    ],
                ],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'crm_transaction_instruction',
                    'strict' => true,
                    'schema' => $this->schema(),
                ],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    protected function payload(string $rawText): array
    {
        return [
            'model' => config('services.openrouter.model'),
            'messages' => [
                [
                    'role' => 'system',
                    'content' => 'Classify Indonesian CRM instructions from web, Telegram, WhatsApp Business, Instagram, or other platforms. For purchases or transactions, extract platform identity and transaction fields. For global commands like "hapus semua data", return intent reset_all and leave transaction fields null. Return unsupported when the instruction is not actionable. Use null when values are absent.',
                ],
                [
                    'role' => 'user',
                    'content' => $rawText,
                ],
            ],
            'response_format' => [
                'type' => 'json_schema',
                'json_schema' => [
                    'name' => 'crm_transaction_instruction',
                    'strict' => true,
                    'schema' => $this->schema(),
                ],
            ],
        ];
    }

    protected function audioFormat(UploadedFile $audio): string
    {
        $extension = strtolower($audio->getClientOriginalExtension() ?: $audio->extension() ?: '');

        return match ($extension) {
            'mp3' => 'mp3',
            'wav' => 'wav',
            'm4a' => 'm4a',
            'ogg', 'oga' => 'ogg',
            'webm' => 'webm',
            'flac' => 'flac',
            'aac' => 'aac',
            default => throw new RuntimeException('Unsupported audio format. Use wav, mp3, m4a, ogg, webm, flac, or aac.'),
        };
    }

    /**
     * @return array<string, mixed>
     */
    protected function schema(): array
    {
        return [
            'type' => 'object',
            'additionalProperties' => false,
            'required' => [
                'intent',
                'platform',
                'platform_user_id',
                'telegram_user_id',
                'customer_name',
                'product',
                'category',
                'quantity',
                'amount',
                'transaction_date',
                'action_summary',
            ],
            'properties' => [
                'intent' => [
                    'type' => 'string',
                    'enum' => ['transaction', 'purchase', 'reset_all', 'unsupported'],
                ],
                'platform' => [
                    'type' => ['string', 'null'],
                    'enum' => ['web', 'telegram', 'whatsapp_business', 'instagram', 'other', null],
                    'description' => 'Normalize platform aliases: WA, WhatsApp, WA Business, and WABA mean whatsapp_business; IG and Insta mean instagram; TG means telegram. Use telegram if the text only provides a Telegram-style user id and no other platform is mentioned.',
                ],
                'platform_user_id' => [
                    'type' => ['string', 'null'],
                    'description' => 'User/customer id or phone/handle from the source platform.',
                ],
                'telegram_user_id' => [
                    'type' => ['string', 'null'],
                    'description' => 'Backward-compatible Telegram user id when explicitly present.',
                ],
                'customer_name' => [
                    'type' => ['string', 'null'],
                ],
                'product' => [
                    'type' => ['string', 'null'],
                ],
                'category' => [
                    'type' => ['string', 'null'],
                ],
                'quantity' => [
                    'type' => ['number', 'null'],
                ],
                'amount' => [
                    'type' => ['number', 'null'],
                ],
                'transaction_date' => [
                    'type' => ['string', 'null'],
                    'description' => 'Date in YYYY-MM-DD. Null if not mentioned.',
                ],
                'action_summary' => [
                    'type' => ['string', 'null'],
                    'description' => 'Short Indonesian explanation of the interpreted action.',
                ],
            ],
        ];
    }
}
