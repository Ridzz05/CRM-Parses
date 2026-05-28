<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Transaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'customer_id',
        'platform',
        'platform_user_id',
        'telegram_user_id',
        'product',
        'category',
        'quantity',
        'amount',
        'transaction_date',
        'raw_text',
        'instruction_id',
    ];

    protected function casts(): array
    {
        return [
            'quantity' => 'decimal:2',
            'amount' => 'decimal:2',
            'transaction_date' => 'date',
        ];
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function instruction(): BelongsTo
    {
        return $this->belongsTo(Instruction::class);
    }
}
