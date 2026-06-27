<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'bank_account_id',
        'transaction_id',
        'bank',
        'type',
        'amount',
        'coin_amount',
        'status',
        'description',
        'raw_payload',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'coin_amount' => 'integer',
            'raw_payload' => 'array',
        ];
    }
}
