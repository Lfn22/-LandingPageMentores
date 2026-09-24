<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex, nofollow">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Entrar — Painel administrativo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="/assets/css/admin.css">
</head>
<body class="admin-login-body">
<main class="admin-login-card">
    <h1>Painel administrativo</h1>
    <?php if ($error !== null): ?>
        <p class="admin-error"><?= e($error) ?></p>
    <?php endif; ?>
    <form method="post" class="admin-login-form">
        <label>E-mail
            <input type="email" name="email" required maxlength="190" autocomplete="username">
        </label>
        <label>Senha
            <input type="password" name="password" required autocomplete="current-password">
        </label>
        <input type="hidden" name="csrf_token" value="<?= e($token) ?>">
        <button type="submit">Entrar</button>
    </form>
</main>
</body>
</html>
