<?php

require __DIR__ . '/../_bootstrap.php';
require APP_PATH . '/lib/auth.php';
require APP_PATH . '/lib/admin.php';

admin_headers();

$wantsJson = str_contains($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json');

function status_respond(bool $wantsJson, int $status, array $payload): void
{
    if ($wantsJson) {
        http_response_code($status);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode($payload, JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(303);
    header('Location: ./');
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    status_respond($wantsJson, 405, ['ok' => false, 'error' => 'method']);
}

auth_require($wantsJson);

if (!csrf_verify($_POST['csrf_token'] ?? null)) {
    status_respond($wantsJson, 403, ['ok' => false, 'error' => 'csrf']);
}

$id = (string) ($_POST['id'] ?? '');
$status = (string) ($_POST['status'] ?? '');

if (!ctype_digit($id) || !in_array($status, LEAD_STATUSES, true)) {
    status_respond($wantsJson, 422, ['ok' => false, 'error' => 'validation']);
}

db()->prepare('UPDATE leads SET status = ? WHERE id = ?')->execute([$status, (int) $id]);

status_respond($wantsJson, 200, ['ok' => true]);
