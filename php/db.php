<?php
// Централізоване підключення до БД через PDO.
function db(): PDO
{
    static $pdo = null;

    if ($pdo === null) {
        $host = getenv('DB_HOST') ?: 'mysql';
        $name = getenv('DB_NAME') ?: 'school_tests';
        $user = getenv('DB_USER') ?: 'app';
        $pass = getenv('DB_PASSWORD') ?: 'app';

        $pdo = new PDO(
            "mysql:host={$host};dbname={$name};charset=utf8mb4",
            $user,
            $pass,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            ]
        );
    }

    return $pdo;
}
