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
        'ban',
        'is_active',
        'type_admin',
        'money',
        'totalmoney',
        'tongnapthang',
        'tongnapthang_reset_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'ban' => 'boolean',
            'is_active' => 'boolean',
            'money' => 'integer',
            'totalmoney' => 'integer',
            'tongnapthang' => 'integer',
            'tongnapthang_reset_at' => 'datetime',
            'password' => 'hashed',
        ];
    }
}
