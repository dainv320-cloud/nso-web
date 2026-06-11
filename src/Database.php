<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private static ?PDO $connection = null;

    public static function connection(): PDO
    {
        if (self::$connection !== null) {
            return self::$connection;
        }

        $connection = env('DB_CONNECTION', 'mysql');
        $host = env('DB_HOST', env('MYSQL_HOST', env('POSTGRES_HOST', '127.0.0.1')));
        $port = env('DB_PORT', env('MYSQL_PORT', env('POSTGRES_PORT', $connection === 'pgsql' ? '5432' : '3306')));
        $database = env('DB_DATABASE', env('MYSQL_DATABASE', env('POSTGRES_DB', 'nso_web')));
        $username = env('DB_USERNAME', env('MYSQL_USER', env('POSTGRES_USER', 'root')));
        $password = env('DB_PASSWORD', env('MYSQL_PASSWORD', env('POSTGRES_PASSWORD', '')));

        $dsn = $connection === 'pgsql'
            ? "pgsql:host={$host};port={$port};dbname={$database}"
            : "mysql:host={$host};port={$port};dbname={$database};charset=utf8mb4";

        self::$connection = new PDO($dsn, $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);

        return self::$connection;
    }
}
