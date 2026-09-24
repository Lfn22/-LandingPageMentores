<?php

require __DIR__ . '/../_bootstrap.php';
require APP_PATH . '/lib/auth.php';
require APP_PATH . '/lib/admin.php';

admin_headers();
auth_require();

header('Content-Type: text/csv; charset=utf-8');
header('Content-Disposition: attachment; filename="leads-' . date('Y-m-d') . '.csv"');

$out = fopen('php://output', 'wb');
fwrite($out, "\xEF\xBB\xBF");

fputcsv($out, ['id', 'data', 'nome', 'telefone', 'email', 'mensagem', 'interesses', 'status', 'consent_at', 'consent_ip'], ';', '"', '', "\r\n");

$stmt = db()->query(
    'SELECT id, name, phone, email, message, interests, status, consent_at, consent_ip, created_at FROM leads ORDER BY created_at DESC'
);

foreach ($stmt as $lead) {
    $interestTitles = [];
    if (!empty($lead['interests'])) {
        $decoded = json_decode((string) $lead['interests'], true);
        if (is_array($decoded)) {
            foreach ($decoded as $interest) {
                $interestTitles[] = (string) ($interest['title'] ?? '');
            }
        }
    }

    fputcsv($out, [
        csv_safe((string) $lead['id']),
        csv_safe($lead['created_at']),
        csv_safe($lead['name']),
        csv_safe(format_phone($lead['phone'])),
        csv_safe($lead['email']),
        csv_safe($lead['message']),
        csv_safe(implode(', ', $interestTitles)),
        csv_safe($lead['status']),
        csv_safe($lead['consent_at']),
        csv_safe($lead['consent_ip']),
    ], ';', '"', '', "\r\n");
}

fclose($out);
