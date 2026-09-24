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

$fontDisplay = google_font_family($site['fonts']['display'] ?? '', 'Archivo');
$fontBody = google_font_family($site['fonts']['body'] ?? '', 'Inter Tight');
$fontsUrl = 'https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $fontDisplay) . ':wght@400;600;800'
    . '&family=' . str_replace(' ', '+', $fontBody) . ':wght@400;600;800&display=swap';

$colorPrimary = valid_hex_color($site['colors']['primary'] ?? null, '#1F4D3A');
$colorAccent = valid_hex_color($site['colors']['accent'] ?? null, '#C8553D');
$colorBg = valid_hex_color($site['colors']['background'] ?? null, '#F6F3EE');
$colorText = valid_hex_color($site['colors']['text'] ?? null, '#1B1F23');

$year = date('Y');
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
            --font-display: "<?= e($fontDisplay) ?>", system-ui, sans-serif;
            --font-body: "<?= e($fontBody) ?>", system-ui, sans-serif;
        }
    </style>
    <script src="assets/js/site.js" defer></script>
</head>
<body>
    <header class="site-header">
        <div class="container site-header__inner">
            <a class="brand" href="#inicio">
                <img src="<?= e(safe_url($site['brand']['logo'] ?? '')) ?>" alt="<?= e($site['brand']['mentor_name'] ?? '') ?>" width="36" height="36">
                <span><?= e($site['brand']['program_name'] ?? '') ?></span>
            </a>
            <nav class="site-nav" aria-label="Navegação principal">
                <a href="#sobre">Sobre</a>
                <?php if (!empty($site['products'])): ?><a href="#ofertas">Ofertas</a><?php endif; ?>
                <?php if (!empty($site['testimonials'])): ?><a href="#depoimentos">Depoimentos</a><?php endif; ?>
                <?php if (!empty($site['faq'])): ?><a href="#faq">FAQ</a><?php endif; ?>
                <a href="#contato">Contato</a>
            </nav>
            <a class="btn btn-primary" href="#contato">Quero conversar</a>
        </div>
    </header>

    <main>
        <section id="inicio" class="hero">
            <div class="container hero__inner">
                <div class="hero__content">
                    <?php if (!empty($site['hero']['eyebrow'])): ?>
                        <p class="hero__eyebrow"><?= e($site['hero']['eyebrow']) ?></p>
                    <?php endif; ?>
                    <h1><?= e($site['hero']['headline'] ?? '') ?></h1>
                    <p class="hero__subheadline"><?= e($site['hero']['subheadline'] ?? '') ?></p>
                    <a class="btn btn-primary" href="#contato"><?= e($site['hero']['cta_label'] ?? 'Quero conversar') ?></a>
                </div>
                <?php if (!empty($site['about']['photo'])): ?>
                    <div class="hero__portrait">
                        <img src="<?= e(safe_url($site['about']['photo'])) ?>" alt="Retrato de <?= e($site['brand']['mentor_name'] ?? '') ?>" width="320" height="400">
                    </div>
                <?php endif; ?>
            </div>
        </section>

        <?php if (!empty($site['about']['text']) || !empty($site['about']['highlights'])): ?>
        <section id="sobre" class="about">
            <div class="container about__inner">
                <h2><?= e($site['about']['title'] ?? 'Sobre') ?></h2>
                <div class="about__text">
                    <?php foreach ((array) ($site['about']['text'] ?? []) as $paragraph): ?>
                        <p><?= e($paragraph) ?></p>
                    <?php endforeach; ?>
                </div>
                <?php if (!empty($site['about']['highlights'])): ?>
                <ul class="about__highlights">
                    <?php foreach ($site['about']['highlights'] as $highlight): ?>
                        <li><?= e($highlight) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($site['products'])): ?>
        <section id="ofertas" class="offers">
            <div class="container">
                <h2>Ofertas</h2>
                <ol class="offers__list">
                    <?php foreach ($site['products'] as $i => $product): ?>
                        <li class="offer">
                            <span class="offer__index"><?= e(str_pad((string) ($i + 1), 2, '0', STR_PAD_LEFT)) ?></span>
                            <div class="offer__body">
                                <h3><?= e($product['title'] ?? '') ?></h3>
                                <p><?= e($product['description'] ?? '') ?></p>
                                <?php if (!empty($product['price'])): ?>
                                    <p class="offer__price"><?= e($product['price']) ?></p>
                                <?php endif; ?>
                                <?php if (!empty($product['link'])): ?>
                                    <a class="offer__link" href="<?= e(safe_url($product['link'])) ?>"<?= is_external_url((string) $product['link']) ? ' rel="noopener" target="_blank"' : '' ?>>Saiba mais</a>
                                <?php endif; ?>
                            </div>
                        </li>
                    <?php endforeach; ?>
                </ol>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($site['testimonials'])):
            $testimonials = $site['testimonials'];
            $featured = array_shift($testimonials);
        ?>
        <section id="depoimentos" class="testimonials">
            <div class="container">
                <h2>Depoimentos</h2>
                <blockquote class="testimonial testimonial--featured">
                    <p>&ldquo;<?= e($featured['text'] ?? '') ?>&rdquo;</p>
                    <cite><?= e($featured['name'] ?? '') ?><?php if (!empty($featured['role'])): ?>, <?= e($featured['role']) ?><?php endif; ?></cite>
                </blockquote>
                <?php if (!empty($testimonials)): ?>
                <ul class="testimonials__list">
                    <?php foreach ($testimonials as $t): ?>
                    <li class="testimonial">
                        <p>&ldquo;<?= e($t['text'] ?? '') ?>&rdquo;</p>
                        <cite><?= e($t['name'] ?? '') ?><?php if (!empty($t['role'])): ?>, <?= e($t['role']) ?><?php endif; ?></cite>
                    </li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>
            </div>
        </section>
        <?php endif; ?>

        <?php if (!empty($site['faq'])): ?>
        <section id="faq" class="faq">
            <div class="container">
                <h2>Perguntas frequentes</h2>
                <?php foreach ($site['faq'] as $item): ?>
                    <details class="faq__item">
                        <summary><?= e($item['question'] ?? '') ?></summary>
                        <p><?= e($item['answer'] ?? '') ?></p>
                    </details>
                <?php endforeach; ?>
            </div>
        </section>
        <?php endif; ?>

        <section id="contato" class="contact">
            <div class="container contact__inner">
                <div class="contact__intro">
                    <h2>Fale com <?= e($site['brand']['mentor_name'] ?? '') ?></h2>
                    <p>Preencha o formulário e receba uma resposta com o próximo passo.</p>
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
            <?php if (!empty($site['social'])): ?>
            <p class="site-footer__social">
                <?php foreach ($site['social'] as $link): ?>
                    <a href="<?= e(safe_url($link['url'] ?? '')) ?>" rel="noopener" target="_blank"><?= e($link['label'] ?? '') ?></a>
                <?php endforeach; ?>
            </p>
            <?php endif; ?>
            <p class="site-footer__copy">&copy; <?= e((string) $year) ?> <?= e($site['brand']['mentor_name'] ?? '') ?></p>
        </div>
    </footer>
</body>
</html>
