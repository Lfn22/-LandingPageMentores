<?php

// Copie este arquivo para app/config/config.php (não versionado) e
// preencha com os dados reais desta cópia.
return [
    'db' => [
        'host' => '127.0.0.1',
        'port' => 3306,
        'name' => 'lpm',
        'user' => 'lpm',
        'pass' => '',
    ],
    // Gere com 64 caracteres aleatórios, ex.: bin2hex(random_bytes(32)).
    'app_secret' => '',
    // Obrigatório, mínimo 16 caracteres: protege o setup.php contra
    // terceiros até o dono concluir a instalação.
    'setup_token' => '',
    'timezone' => 'America/Sao_Paulo',
    'session_name' => 'LPMSESSID',
    'min_fill_seconds' => 3,
    'rate_limit' => [
        'lead' => ['max' => 5, 'window' => 600],
        // Usado pelo login do painel (Fase 2).
        'login' => ['max' => 5, 'window' => 900],
    ],
];
