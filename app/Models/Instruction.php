<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Instruction extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SUCCESS = 'success';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'source',
        'platform',
        'platform_user_id',
        'platform_message_id',
        'external_message_id',
        'telegram_user_id',
        'raw_text',
        'parsed_payload',
        'status',
        'error_message',
        'processed_at',
    ];

    protected function casts(): array
    {
        return [
            'parsed_payload' => 'array',
            'processed_at' => 'datetime',
        ];
    }

    public function transaction(): HasOne
    {
        return $this->hasOne(Transaction::class);
    }
}
