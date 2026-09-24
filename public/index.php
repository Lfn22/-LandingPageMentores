<?php

require __DIR__ . '/_bootstrap.php';

send_security_headers();

$site = site_config();
$flash = flash_take('lead');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($site['seo']['title']) ?></title>
    <meta name="description" content="<?= e($site['seo']['description']) ?>">
</head>
<body>
    <header>
        <p><?= e($site['brand']['mentor_name']) ?> — <?= e($site['brand']['program_name']) ?></p>
    </header>

    <main>
        <section id="contato">
            <h2>Fale com <?= e($site['brand']['mentor_name']) ?></h2>
            <?php require APP_PATH . '/views/lead-form.php'; ?>
        </section>
    </main>

    <footer>
        <p><?= e($site['lgpd']['footer_notice']) ?></p>
    </footer>
</body>
</html>
