<?php

declare(strict_types=1);

require __DIR__ . '/../src/bootstrap.php';

use App\Database;

$migrationDir = dirname(__DIR__) . '/migrations';
$files = glob($migrationDir . '/*.sql') ?: [];

sort($files);

if ($files === []) {
    echo "No migrations found.\n";
    exit(0);
}

$connection = Database::connection();

foreach ($files as $file) {
    echo 'Running ' . basename($file) . "...\n";
    $connection->exec(file_get_contents($file));
}

echo "Migrations completed.\n";
