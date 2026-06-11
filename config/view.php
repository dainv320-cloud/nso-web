<?php

return [
    'paths' => [
        resource_path('views'),
        base_path('src/Views'),
    ],
    'compiled' => env('VIEW_COMPILED_PATH', realpath(storage_path('framework/views')) ?: storage_path('framework/views')),
];
