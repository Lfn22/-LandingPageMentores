<?php

require __DIR__ . '/_bootstrap.php';

send_security_headers();

function setup_page(string $title, string $body): void
{
    echo '<!DOCTYPE html><html lang="pt-BR"><head><meta charset="utf-8">'
        . '<title>' . e($title) . '</title></head><body>' . $body . '</body></html>';
}

$lockFile = APP_PATH . '/storage/setup.lock';

if (is_file($lockFile)) {
    http_response_code(403);
    setup_page('Instalação já concluída', '<p>Instalação já concluída.</p>');
    exit;
}

$setupToken = (string) app_config('setup_token', '');
if (mb_strlen($setupToken) < 16) {
    http_response_code(500);
    setup_page(
        'Configuração incompleta',
        '<p>Defina um setup_token com pelo menos 16 caracteres em app/config/config.php.</p>'
    );
    exit;
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_verify($_POST['csrf_token'] ?? null)) {
        http_response_code(403);
        setup_page('Token inválido', '<p>Token de segurança inválido. Recarregue a página e tente novamente.</p>');
        exit;
    }

    if (!hash_equals($setupToken, (string) ($_POST['setup_token'] ?? ''))) {
        $errors['setup_token'] = 'Token de instalação incorreto.';
    }

    $email = trim((string) ($_POST['email'] ?? ''));
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors['email'] = 'Informe um e-mail válido.';
    }

    $password = (string) ($_POST['password'] ?? '');
    $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
    if (mb_strlen($password) < 10) {
        $errors['password'] = 'A senha precisa ter ao menos 10 caracteres.';
    } elseif ($password !== $passwordConfirm) {
        $errors['password_confirm'] = 'As senhas não conferem.';
    }

    if (empty($errors)) {
        try {
            $pdo = db();
        } catch (Throwable $ex) {
            http_response_code(500);
            setup_page(
                'Erro de conexão',
                '<p>Não foi possível conectar ao banco. Verifique app/config/config.php.</p>'
            );
            exit;
        }

        $schema = file_get_contents(APP_PATH . '/database/schema.sql');
        foreach (preg_split('/;\s*\n/', $schema) as $statement) {
            $statement = trim($statement);
            if ($statement !== '') {
                $pdo->exec($statement);
            }
        }

        $userCount = (int) $pdo->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($userCount === 0) {
            $stmt = $pdo->prepare('INSERT INTO users (email, password_hash, created_at) VALUES (?, ?, ?)');
            $stmt->execute([$email, password_hash($password, PASSWORD_DEFAULT), now_db()]);
        }

        $storageDir = APP_PATH . '/storage';
        if (!is_writable($storageDir) || file_put_contents($lockFile, now_db(), LOCK_EX) === false) {
            http_response_code(500);
            setup_page(
                'Erro ao finalizar',
                '<p>Não foi possível gravar app/storage/setup.lock. Verifique as permissões da pasta.</p>'
            );
            exit;
        }

        setup_page(
            'Instalação concluída',
            '<p>Instalação concluída. Por segurança, apague o arquivo public/setup.php do servidor.</p>'
        );
        exit;
    }
}

$token = csrf_token();

ob_start();
?>
<h1>Instalação da cópia</h1>
<?php foreach ($errors as $error): ?>
    <p class="field-error"><?= e($error) ?></p>
<?php endforeach; ?>
<form method="post">
    <p><label>E-mail do dono<br><input type="email" name="email" required maxlength="190"></label></p>
    <p><label>Senha (mínimo 10 caracteres)<br><input type="password" name="password" required minlength="10"></label></p>
    <p><label>Confirme a senha<br><input type="password" name="password_confirm" required minlength="10"></label></p>
    <p><label>Token de instalação<br><input type="text" name="setup_token" required></label></p>
    <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
    <p><button type="submit">Instalar</button></p>
</form>
<?php
$body = ob_get_clean();
setup_page('Instalação', $body);
