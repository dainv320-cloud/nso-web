<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory;
    use Notifiable;

    protected $fillable = [
        'name',
        'username',
        'email',
        'password',
        'status',
        'activated',
        'active',
        'role',
        'balance',
        'tongnap',
        'tongNapThang',
        'tongNapTuan',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'status' => 'integer',
            'activated' => 'boolean',
            'active' => 'boolean',
            'role' => 'integer',
            'balance' => 'integer',
            'tongnap' => 'integer',
            'tongNapThang' => 'integer',
            'tongNapTuan' => 'integer',
            'password' => 'hashed',
        ];
    }
}
