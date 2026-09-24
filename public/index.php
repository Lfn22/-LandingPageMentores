<?php

require __DIR__ . '/_bootstrap.php';

send_security_headers();

$site = site_config();
$flash = flash_take('lead');

function google_font_family(?string $name, string $fallback): string
{
    $name = (string) $name;
    return preg_match('/^[A-Za-z0-9 ]{1,40}$/', $name) ? $name : $fallback;
}

function safe_url(?string $url): string
{
    $url = (string) $url;
    foreach (['https://', 'http://', '/', '#', 'assets/'] as $prefix) {
        if (str_starts_with($url, $prefix)) {
            return $url;
        }
    }
    return '#';
}

function is_external_url(string $url): bool
{
    return str_starts_with($url, 'http://') || str_starts_with($url, 'https://');
}

$fontDisplay = google_font_family($site['fonts']['display'] ?? '', 'Cinzel');
$fontBody = google_font_family($site['fonts']['body'] ?? '', 'Inter');
$fontsUrl = 'https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $fontDisplay) . ':wght@400;600;800'
    . '&family=' . str_replace(' ', '+', $fontBody) . ':wght@400;600;800&display=swap';

$colorPrimary = valid_hex_color($site['colors']['primary'] ?? null, '#C8A24C');
$colorAccent = valid_hex_color($site['colors']['accent'] ?? null, '#9A7428');
$colorBg = valid_hex_color($site['colors']['background'] ?? null, '#2F3E46');
$colorText = valid_hex_color($site['colors']['text'] ?? null, '#F3EFE6');

$year = date('Y');

$commercialUrl = (string) ($site['commercial']['url'] ?? '');
$commercialLabel = $site['commercial']['label'] ?? 'Falar com o comercial';
$showCommercial = is_external_url($commercialUrl);

$adminUrl = $site['admin_url'] ?? '/admin/';

$brandAlt = trim(($site['brand']['mentor_name'] ?? '') . ' — ' . ($site['brand']['program_name'] ?? ''), ' —');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= e($site['seo']['title'] ?? ($site['brand']['mentor_name'] ?? '')) ?></title>
    <meta name="description" content="<?= e($site['seo']['description'] ?? '') ?>">
    <meta property="og:title" content="<?= e($site['seo']['title'] ?? '') ?>">
    <meta property="og:description" content="<?= e($site['seo']['description'] ?? '') ?>">
    <meta property="og:image" content="<?= e(safe_url($site['seo']['og_image'] ?? '')) ?>">
    <link rel="icon" href="assets/img/favicon.svg" type="image/svg+xml">

    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="stylesheet" href="<?= e($fontsUrl) ?>">
    <link rel="stylesheet" href="assets/css/site.css">

    <style nonce="<?= e(csp_nonce()) ?>">
        :root {
            --color-primary: <?= e($colorPrimary) ?>;
            --color-accent: <?= e($colorAccent) ?>;
            --color-bg: <?= e($colorBg) ?>;
            --color-text: <?= e($colorText) ?>;
            --font-display: "<?= e($fontDisplay) ?>", serif;
            --font-body: "<?= e($fontBody) ?>", system-ui, sans-serif;
        }
    </style>
    <script src="assets/js/site.js" defer></script>
</head>
<body>
    <header class="site-header">
        <div class="container site-header__inner">
            <a class="brand" href="#contato">
                <img src="<?= e(safe_url($site['brand']['logo'] ?? '')) ?>" alt="<?= e($brandAlt) ?>" width="220" height="138">
            </a>
            <div class="site-header__actions">
                <?php if ($showCommercial): ?>
                    <a class="btn btn-primary" href="<?= e($commercialUrl) ?>" target="_blank" rel="noopener"><?= e($commercialLabel) ?></a>
                <?php endif; ?>
                <a class="btn btn-pill" href="<?= e(safe_url($adminUrl)) ?>">Painel do dono</a>
            </div>
        </div>
    </header>

    <main>
        <section id="contato" class="contact">
            <div class="container contact__inner">
                <div class="contact__intro">
                    <h1>Fale com <?= e($site['brand']['mentor_name'] ?? '') ?></h1>
                    <p>Preencha o formulário e nossa equipe retorna com o próximo passo.</p>
                </div>
                <?php require APP_PATH . '/views/lead-form.php'; ?>
            </div>
        </section>
    </main>

    <footer class="site-footer">
        <div class="container">
            <p class="site-footer__lgpd">
                <?= e($site['lgpd']['footer_notice'] ?? '') ?>
                <?php if (!empty($site['lgpd']['policy_url'])): ?>
                    <a href="<?= e(safe_url($site['lgpd']['policy_url'])) ?>">Política de Privacidade</a>
                <?php endif; ?>
            </p>
            <p class="site-footer__copy">&copy; <?= e((string) $year) ?> <?= e($site['brand']['mentor_name'] ?? '') ?></p>
        </div>
    </footer>
</body>
</html>
