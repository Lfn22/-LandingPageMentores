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

function asset_version(string $relativePath): int
{
    $path = __DIR__ . '/' . $relativePath;
    $mtime = @filemtime($path);
    return $mtime !== false ? $mtime : time();
}

$fontDisplay = google_font_family($site['fonts']['display'] ?? '', 'Archivo');
$fontBody = google_font_family($site['fonts']['body'] ?? '', 'Inter');
$fontsUrl = 'https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $fontDisplay) . ':wght@400;600;700'
    . '&family=' . str_replace(' ', '+', $fontBody) . ':wght@400;600;700&display=swap';

$colorPrimary = valid_hex_color($site['colors']['primary'] ?? null, '#263039');
$colorAccent = valid_hex_color($site['colors']['accent'] ?? null, '#C8A24C');
$colorBg = valid_hex_color($site['colors']['background'] ?? null, '#263039');
$colorText = valid_hex_color($site['colors']['text'] ?? null, '#F3EFE6');

$commercialUrl = (string) ($site['commercial']['url'] ?? '');
$commercialLabel = $site['commercial']['label'] ?? 'Falar com o comercial';
$showCommercial = is_external_url($commercialUrl);

$adminUrl = $site['admin_url'] ?? '/admin/';

$brandAlt = trim(($site['brand']['mentor_name'] ?? '') . ' — ' . ($site['brand']['program_name'] ?? ''), ' —');

$waitlist = $site['waitlist'] ?? [];

$cssVersion = asset_version('assets/css/site.css');
$jsVersion = asset_version('assets/js/site.js');
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
    <link rel="stylesheet" href="assets/css/site.css?v=<?= (int) $cssVersion ?>">

    <style nonce="<?= e(csp_nonce()) ?>">
        :root {
            --color-primary: <?= e($colorPrimary) ?>;
            --color-accent: <?= e($colorAccent) ?>;
            --color-bg: <?= e($colorBg) ?>;
            --color-text: <?= e($colorText) ?>;
            --font-display: "<?= e($fontDisplay) ?>", system-ui, sans-serif;
            --font-body: "<?= e($fontBody) ?>", system-ui, sans-serif;
        }
    </style>
    <script src="assets/js/site.js?v=<?= (int) $jsVersion ?>" defer></script>
</head>
<body>
    <main class="page" id="contato">
        <div class="container waitlist">
            <div class="waitlist__intro">
                <img class="waitlist__logo" src="<?= e(safe_url($site['brand']['logo'] ?? '')) ?>" alt="<?= e($brandAlt) ?>" width="300" height="188">

                <h1 class="waitlist__headline"><?= e($waitlist['headline'] ?? '') ?></h1>
                <div class="waitlist__hairline" aria-hidden="true"></div>
                <p class="waitlist__paragraph"><?= e($waitlist['paragraph'] ?? '') ?></p>

                <?php if (!empty($waitlist['bullets'])): ?>
                <ul class="waitlist__bullets">
                    <?php foreach ($waitlist['bullets'] as $bullet): ?>
                        <li>
                            <svg class="check-icon" viewBox="0 0 20 20" width="18" height="18" aria-hidden="true">
                                <path d="M4 10.5 L8 14.5 L16 5.5" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/>
                            </svg>
                            <span><?= e($bullet) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>

            <div class="waitlist__card">
                <?php require APP_PATH . '/views/lead-form.php'; ?>
            </div>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <div class="site-footer__actions">
                <a class="btn btn-pill" href="<?= e(safe_url($adminUrl)) ?>">Ver inscrições</a>
                <?php if ($showCommercial): ?>
                    <a class="btn btn-pill" href="<?= e($commercialUrl) ?>" target="_blank" rel="noopener"><?= e($commercialLabel) ?></a>
                <?php endif; ?>
            </div>
            <?php if (!empty($site['lgpd']['footer_notice'])): ?>
                <p class="site-footer__lgpd"><?= e($site['lgpd']['footer_notice']) ?></p>
            <?php endif; ?>
        </div>
    </footer>
</body>
</html>
