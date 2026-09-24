<?php

require __DIR__ . '/../_bootstrap.php';

send_security_headers();

$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

function lead_respond(bool $wantsJson, int $status, array $payload, string $redirectTo = '../index.php#contato', ?array $flash = null): void
{
    if ($wantsJson) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    if ($flash === null && empty($payload['ok'])) {
        $flash = ['status' => 'error', 'errors' => ['form' => $payload['message'] ?? 'Tente novamente.'], 'old' => $_POST];
    }
    if ($flash !== null) {
        flash_set('lead', $flash);
    }
    http_response_code(303);
    header('Location: ' . $redirectTo);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    if (!$wantsJson) {
        http_response_code(405);
        header('Allow: POST');
        header('Content-Type: text/plain; charset=utf-8');
        echo 'Método não permitido.';
        exit;
    }
    lead_respond($wantsJson, 405, ['ok' => false, 'error' => 'method', 'message' => 'Método não permitido.']);
}

$site = site_config();
$ip = client_ip();

if (!rate_limit_hit(db(), 'lead', $ip, (int) app_config('rate_limit.lead.max', 5), (int) app_config('rate_limit.lead.window', 600))) {
    lead_respond(
        $wantsJson,
        429,
        ['ok' => false, 'error' => 'rate_limited', 'message' => 'Muitos envios. Tente novamente em alguns minutos.'],
        '../index.php#contato',
        ['status' => 'error', 'errors' => ['form' => 'Muitos envios. Tente novamente em alguns minutos.'], 'old' => $_POST]
    );
}

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    lead_respond($wantsJson, 403, ['ok' => false, 'error' => 'csrf', 'message' => 'Sessão expirada. Recarregue a página.']);
}

if (trim((string) ($_POST['website'] ?? '')) !== '') {
    // Honeypot preenchido: finge sucesso para não revelar a defesa a bots.
    lead_respond(
        $wantsJson,
        200,
        ['ok' => true, 'message' => 'Recebemos seu contato.', 'whatsapp_url' => whatsapp_url($site)],
        '../index.php?enviado=1#contato',
        ['status' => 'ok', 'name' => '']
    );
}

$age = form_ts_age($_POST['form_ts'] ?? null);
if ($age === null || $age < (int) app_config('min_fill_seconds', 3)) {
    lead_respond(
        $wantsJson,
        422,
        ['ok' => false, 'error' => 'too_fast', 'message' => 'Aguarde alguns segundos e tente novamente.'],
        '../index.php#contato',
        ['status' => 'error', 'errors' => ['form' => 'Aguarde alguns segundos e tente novamente.'], 'old' => $_POST]
    );
}

['data' => $data, 'errors' => $errors] = lead_validate($_POST, $site);

if (!empty($errors)) {
    lead_respond(
        $wantsJson,
        422,
        ['ok' => false, 'error' => 'validation', 'errors' => $errors],
        '../index.php#contato',
        ['status' => 'error', 'errors' => $errors, 'old' => $_POST]
    );
}

try {
    lead_insert(db(), $data, $site, $ip, (string) ($_SERVER['HTTP_USER_AGENT'] ?? ''));
} catch (Throwable $ex) {
    error_log('lead_insert falhou: ' . $ex->getMessage());
    lead_respond($wantsJson, 500, ['ok' => false, 'error' => 'server', 'message' => 'Não foi possível gravar seu contato agora.']);
}

$firstName = explode(' ', $data['name'])[0] ?? '';

lead_respond(
    $wantsJson,
    200,
    ['ok' => true, 'message' => 'Recebemos seu contato, obrigado!', 'whatsapp_url' => whatsapp_url($site, $data['name'])],
    '../index.php?enviado=1#contato',
    ['status' => 'ok', 'name' => $firstName]
);
