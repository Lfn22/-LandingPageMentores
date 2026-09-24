<?php

// Configuração usada apenas pelo ambiente Docker local (não é o config.php de produção).
return [
    'db' => [
        'host' => 'db',
        'port' => 3306,
        'name' => 'lpm',
        'user' => 'lpm',
        'pass' => 'lpm_dev_pass',
    ],
    'app_secret' => '35e67264f2d5692aae37a61cc71983d03d282d5aad6e906f0745e6a20a5a3163',
    'setup_token' => 'docker-setup-token-0123456789',
    'timezone' => 'America/Sao_Paulo',
    'session_name' => 'LPMSESSID',
    'min_fill_seconds' => 3,
    'rate_limit' => [
        'lead' => ['max' => 5, 'window' => 600],
        'login' => ['max' => 5, 'window' => 900],
    ],
];
