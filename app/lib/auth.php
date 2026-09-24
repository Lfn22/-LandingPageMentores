<?php

const AUTH_IDLE_SECONDS = 7200;

// Hash bcrypt fixo (nunca usado como senha real) para manter tempo
// constante quando o e-mail informado não existe, evitando enumeração
// de usuários por diferença de tempo de resposta.
const AUTH_DUMMY_HASH = '$2y$10$hpJBUyZoLGw4RZ8omEDAf.FHLLRUSRVf66XuE80fmjJd6eM6g5v2i';

function admin_headers(): void
{
    send_security_headers();
    header('X-Robots-Tag: noindex, nofollow');
    header('Cache-Control: no-store');
}

function auth_login(PDO $pdo, string $email, string $password): bool
{
    $stmt = $pdo->prepare('SELECT id, password_hash FROM users WHERE email = ?');
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    $hash = $user['password_hash'] ?? AUTH_DUMMY_HASH;
    $valid = password_verify($password, $hash);

    if (!$user || !$valid) {
        return false;
    }

    session_regenerate_id(true);
    unset($_SESSION['csrf_token']);
    $_SESSION['uid'] = (int) $user['id'];
    $_SESSION['last_seen'] = time();

    $pdo->prepare('UPDATE users SET last_login_at = ? WHERE id = ?')
        ->execute([now_db(), $user['id']]);

    return true;
}

function auth_user(): ?int
{
    if (empty($_SESSION['uid']) || empty($_SESSION['last_seen'])) {
        return null;
    }

    if (time() - (int) $_SESSION['last_seen'] > AUTH_IDLE_SECONDS) {
        auth_logout();
        return null;
    }

    $_SESSION['last_seen'] = time();

    return (int) $_SESSION['uid'];
}

function auth_require(bool $json = false): void
{
    if (auth_user() !== null) {
        return;
    }

    if ($json) {
        http_response_code(401);
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['ok' => false, 'error' => 'auth'], JSON_UNESCAPED_UNICODE);
        exit;
    }

    http_response_code(302);
    header('Location: /admin/');
    exit;
}

function auth_logout(): void
{
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params['path'],
            $params['domain'],
            $params['secure'],
            $params['httponly']
        );
    }

    session_destroy();
}
