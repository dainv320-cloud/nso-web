<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Database;
use App\Response;

final class HealthController
{
    public function show(): never
    {
        Database::connection()->query('select 1');

        Response::json([
            'status' => 'ok',
            'database' => 'postgresql',
        ]);
    }
}
