<?php

require __DIR__ . '/../_bootstrap.php';
require APP_PATH . '/lib/auth.php';

admin_headers();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Método não permitido.';
    exit;
}

auth_require();

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    http_response_code(403);
    header('Content-Type: text/plain; charset=utf-8');
    echo 'Token de segurança inválido.';
    exit;
}

auth_logout();

http_response_code(303);
header('Location: ./');
