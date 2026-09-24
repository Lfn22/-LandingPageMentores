<?php

function e(?string $s): string
{
    return htmlspecialchars($s ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_verify(?string $token): bool
{
    return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

function form_ts_issue(): string
{
    $time = (string) time();
    return $time . '.' . hash_hmac('sha256', $time, (string) app_config('app_secret'));
}

function form_ts_age(?string $value): ?int
{
    if (!is_string($value) || !str_contains($value, '.')) {
        return null;
    }
    [$time, $signature] = explode('.', $value, 2);
    if (!ctype_digit($time)) {
        return null;
    }
    $expected = hash_hmac('sha256', $time, (string) app_config('app_secret'));
    if (!hash_equals($expected, $signature)) {
        return null;
    }
    return time() - (int) $time;
}

function client_ip(): string
{
    return $_SERVER['REMOTE_ADDR'] ?? '';
}

function rate_limit_hit(PDO $pdo, string $action, string $ip, int $max, int $windowSeconds): bool
{
    // Limpeza oportunista de tentativas com mais de 1 dia.
    $pdo->prepare('DELETE FROM rate_limits WHERE created_at < ?')
        ->execute([date('Y-m-d H:i:s', time() - 86400)]);

    $stmt = $pdo->prepare('SELECT COUNT(*) FROM rate_limits WHERE ip = ? AND action = ? AND created_at >= ?');
    $stmt->execute([$ip, $action, date('Y-m-d H:i:s', time() - $windowSeconds)]);

    if ((int) $stmt->fetchColumn() >= $max) {
        return false;
    }

    $pdo->prepare('INSERT INTO rate_limits (ip, action, created_at) VALUES (?, ?, ?)')
        ->execute([$ip, $action, now_db()]);

    return true;
}

function csp_nonce(): string
{
    static $nonce = null;
    if ($nonce === null) {
        $nonce = base64_encode(random_bytes(16));
    }
    return $nonce;
}

function send_security_headers(): void
{
    $nonce = csp_nonce();
    header(
        "Content-Security-Policy: default-src 'self'; script-src 'self'; "
        . "style-src 'self' 'nonce-{$nonce}' https://fonts.googleapis.com; "
        . "font-src https://fonts.gstatic.com; img-src 'self' data:; connect-src 'self'; "
        . "form-action 'self'; frame-ancestors 'none'; base-uri 'self'"
    );
    header('X-Content-Type-Options: nosniff');
    header('Referrer-Policy: strict-origin-when-cross-origin');
    header('X-Frame-Options: DENY');
    if (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
        header('Strict-Transport-Security: max-age=31536000');
    }
}

function valid_hex_color(?string $value, string $fallback): string
{
    if (is_string($value) && preg_match('/^#[0-9a-fA-F]{3}([0-9a-fA-F]{3})?$/', $value)) {
        return $value;
    }
    return $fallback;
}
