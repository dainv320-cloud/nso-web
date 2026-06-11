<?php

return [
    'default' => env('MAIL_MAILER', 'log'),
    'mailers' => [
        'log' => [
            'transport' => 'log',
            'channel' => env('MAIL_LOG_CHANNEL'),
        ],
        'smtp' => [
            'transport' => 'smtp',
            'host' => env('MAIL_HOST', '127.0.0.1'),
            'port' => env('MAIL_PORT', 2525),
            'username' => env('MAIL_USERNAME'),
            'password' => env('MAIL_PASSWORD'),
            'encryption' => env('MAIL_ENCRYPTION'),
        ],
    ],
    'from' => [
        'address' => env('MAIL_FROM_ADDRESS', env('MAIL_FROM', 'no-reply@ninjaschoolblue.vn')),
        'name' => env('MAIL_FROM_NAME', 'Ninja School Blue'),
    ],
];
