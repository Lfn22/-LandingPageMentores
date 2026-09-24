<?php

function lead_validate(array $input, array $site): array
{
    $data = [];
    $errors = [];

    $name = trim((string) ($input['name'] ?? ''));
    if (mb_strlen($name) < 2 || mb_strlen($name) > 120) {
        $errors['name'] = 'Informe seu nome (2 a 120 caracteres).';
    }
    $data['name'] = $name;

    $phone = preg_replace('/\D+/', '', (string) ($input['phone'] ?? ''));
    if (!in_array(strlen($phone), [10, 11], true)) {
        $errors['phone'] = 'Informe um telefone válido com DDD.';
    }
    $data['phone'] = $phone;

    $email = trim((string) ($input['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL) || mb_strlen($email) > 190) {
        $errors['email'] = 'Informe um e-mail válido.';
    }
    $data['email'] = $email;

    $message = trim((string) ($input['message'] ?? ''));
    if (mb_strlen($message) < 1 || mb_strlen($message) > 2000) {
        $errors['message'] = 'Escreva uma mensagem (até 2000 caracteres).';
    }
    $data['message'] = $message;

    if (($input['consent'] ?? null) !== '1') {
        $errors['consent'] = 'É necessário autorizar o uso dos seus dados.';
    }

    $validIds = array_column($site['products'], 'id');
    $requested = $input['interests'] ?? [];
    if (!is_array($requested)) {
        $requested = [];
    }
    $requested = array_values(array_unique(array_map('strval', $requested)));

    $chosen = [];
    foreach ($requested as $id) {
        if (!in_array($id, $validIds, true)) {
            $errors['interests'] = 'Selecione apenas interesses válidos.';
            continue;
        }
        foreach ($site['products'] as $product) {
            if ($product['id'] === $id) {
                $chosen[] = ['id' => $product['id'], 'title' => $product['title']];
                break;
            }
        }
    }
    $data['interests'] = $chosen === [] ? null : json_encode($chosen, JSON_UNESCAPED_UNICODE);

    return ['data' => $data, 'errors' => $errors];
}

function lead_insert(PDO $pdo, array $data, array $site, string $ip, string $userAgent): int
{
    $now = now_db();
    $stmt = $pdo->prepare(
        'INSERT INTO leads (name, phone, email, message, interests, status, consent_at, consent_ip, consent_text, ip, user_agent, created_at)
         VALUES (:name, :phone, :email, :message, :interests, :status, :consent_at, :consent_ip, :consent_text, :ip, :user_agent, :created_at)'
    );
    $stmt->execute([
        'name' => $data['name'],
        'phone' => $data['phone'],
        'email' => $data['email'],
        'message' => $data['message'],
        'interests' => $data['interests'],
        'status' => 'novo',
        'consent_at' => $now,
        'consent_ip' => $ip,
        'consent_text' => $site['lgpd']['consent_text'],
        'ip' => $ip,
        'user_agent' => mb_substr($userAgent, 0, 255),
        'created_at' => $now,
    ]);

    return (int) $pdo->lastInsertId();
}

function whatsapp_url(array $site, string $leadName = ''): string
{
    $firstName = trim(explode(' ', trim($leadName))[0] ?? '');

    $message = $firstName !== ''
        ? str_replace('{nome}', $firstName, $site['whatsapp']['after_lead_message'])
        : $site['whatsapp']['default_message'];

    return 'https://wa.me/' . $site['whatsapp']['number'] . '?text=' . rawurlencode($message);
}
