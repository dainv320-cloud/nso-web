<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'user_id',
        'bank_account_id',
        'currency_id',
        'trans_id',
        'type',
        'amount',
        'balance',
        'coin_amount',
        'status',
        'description',
        'raw_payload',
        'user_balance_snapshot_before',
        'extra',
        'player_name',
        'received',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'balance' => 'integer',
            'coin_amount' => 'integer',
            'raw_payload' => 'array',
            'user_balance_snapshot_before' => 'array',
            'received' => 'integer',
        ];
    }
}
