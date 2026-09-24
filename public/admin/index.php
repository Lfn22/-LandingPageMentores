<?php

require __DIR__ . '/../_bootstrap.php';
require APP_PATH . '/lib/auth.php';
require APP_PATH . '/lib/admin.php';

admin_headers();

$site = site_config();
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ip = client_ip();
    $max = (int) app_config('rate_limit.login.max', 5);
    $window = (int) app_config('rate_limit.login.window', 900);

    if (!rate_limit_hit(db(), 'login', $ip, $max, $window)) {
        http_response_code(429);
        $error = 'Muitas tentativas. Tente novamente mais tarde.';
    } elseif (!csrf_verify($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        $error = 'Sessão expirada. Recarregue a página.';
    } else {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        if (auth_login(db(), $email, $password)) {
            http_response_code(303);
            header('Location: ./');
            exit;
        }

        http_response_code(401);
        $error = 'E-mail ou senha inválidos.';
    }
}

$uid = auth_user();

if ($uid === null) {
    $token = csrf_token();
    require APP_PATH . '/views/admin/login.php';
    exit;
}

$kpis = admin_kpis(db());
$radar = admin_radar(db(), $site);
$q = trim((string) ($_GET['q'] ?? ''));
$leads = admin_leads(db(), $q);
$csrfLogout = csrf_token();

require APP_PATH . '/views/admin/panel.php';
