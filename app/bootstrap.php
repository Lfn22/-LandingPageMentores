<?php

$configFile = getenv('APP_CONFIG_FILE') ?: ($_SERVER['APP_CONFIG_FILE'] ?? APP_PATH . '/config/config.php');

if (!is_file($configFile)) {
    http_response_code(500);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Configuração ausente: copie app/config/config.example.php para app/config/config.php.';
    exit;
}

$GLOBALS['__app_config'] = require $configFile;

function app_config(?string $key = null, mixed $default = null): mixed
{
    $config = $GLOBALS['__app_config'];
    if ($key === null) {
        return $config;
    }
    $value = $config;
    foreach (explode('.', $key) as $part) {
        if (!is_array($value) || !array_key_exists($part, $value)) {
            return $default;
        }
        $value = $value[$part];
    }
    return $value;
}

function site_config(): array
{
    static $site = null;
    if ($site === null) {
        $site = require APP_PATH . '/config/site.php';
    }
    return $site;
}

date_default_timezone_set(app_config('timezone', 'America/Sao_Paulo'));
ini_set('display_errors', '0');
ini_set('log_errors', '1');

require_once APP_PATH . '/lib/db.php';
require_once APP_PATH . '/lib/session.php';
require_once APP_PATH . '/lib/security.php';
require_once APP_PATH . '/lib/leads.php';

session_start_secure();
