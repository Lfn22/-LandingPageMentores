<?php

function db(): PDO
{
    static $pdo = null;
    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            app_config('db.host'),
            app_config('db.port', 3306),
            app_config('db.name')
        );
        $pdo = new PDO($dsn, app_config('db.user'), app_config('db.pass'), [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
        ]);
    }
    return $pdo;
}

function now_db(): string
{
    return date('Y-m-d H:i:s');
}
