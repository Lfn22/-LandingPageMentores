<?php

function session_start_secure(): void
{
    session_name(app_config('session_name', 'LPMSESSID'));
    ini_set('session.use_strict_mode', '1');
    ini_set('session.use_only_cookies', '1');
    ini_set('session.gc_maxlifetime', '7200');
    session_set_cookie_params([
        'lifetime' => 0,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
    ]);
    session_start();
}

function flash_set(string $key, array $data): void
{
    $_SESSION['flash'][$key] = $data;
}

function flash_take(string $key): ?array
{
    $data = $_SESSION['flash'][$key] ?? null;
    unset($_SESSION['flash'][$key]);
    return $data;
}
