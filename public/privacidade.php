<?php

require __DIR__ . '/_bootstrap.php';

send_security_headers();

$site = site_config();

function asset_version_privacy(string $relativePath): int
{
    $path = __DIR__ . '/' . $relativePath;
    $mtime = @filemtime($path);
    return $mtime !== false ? $mtime : time();
}

function safe_url_privacy(?string $url): string
{
    $url = (string) $url;
    foreach (['https://', 'http://', '/', '#', 'assets/'] as $prefix) {
        if (str_starts_with($url, $prefix)) {
            return $url;
        }
    }
    return '#';
}

$colorPrimary = valid_hex_color($site['colors']['primary'] ?? null, '#263039');
$colorAccent = valid_hex_color($site['colors']['accent'] ?? null, '#C8A24C');
$colorBg = valid_hex_color($site['colors']['background'] ?? null, '#263039');
$colorText = valid_hex_color($site['colors']['text'] ?? null, '#F3EFE6');

$fontDisplay = preg_match('/^[A-Za-z0-9 ]{1,40}$/', (string) ($site['fonts']['display'] ?? '')) ? $site['fonts']['display'] : 'Archivo';
$fontBody = preg_match('/^[A-Za-z0-9 ]{1,40}$/', (string) ($site['fonts']['body'] ?? '')) ? $site['fonts']['body'] : 'Inter';
$fontsUrl = 'https://fonts.googleapis.com/css2?family=' . str_replace(' ', '+', $fontDisplay) . ':wght@400;600;700'
    . '&family=' . str_replace(' ', '+', $fontBody) . ':wght@400;600;700&display=swap';

$brandName = trim(($site['brand']['mentor_name'] ?? '') . ' — ' . ($site['brand']['program_name'] ?? ''), ' —');
$contactEmail = (string) ($site['lgpd']['contact_email'] ?? '');

$cssVersion = asset_version_privacy('assets/css/site.css');
?>
<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="theme-color" content="<?= e($colorBg) ?>">
    <title>Política de Privacidade — <?= e($brandName) ?></title>
    <meta name="description" content="Como <?= e($brandName) ?> coleta, usa e protege os dados enviados pelo formulário de contato, em conformidade com a LGPD.">
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
        .policy {
            padding: 3rem 0 2rem;
        }
        .policy h1 {
            font-size: 1.75rem;
        }
        .policy h2 {
            font-size: 1.15rem;
            margin-top: 2rem;
        }
        .policy p,
        .policy li {
            opacity: 0.92;
        }
        .policy ul {
            list-style: disc;
            padding-left: 1.25rem;
            margin: 0 0 1em;
        }
        .policy li {
            margin-bottom: 0.4em;
        }
        .policy a {
            text-decoration: underline;
        }
        .policy__back {
            display: inline-block;
            margin-bottom: 1.5rem;
        }
        .policy__logo {
            width: 160px;
            height: auto;
            margin-bottom: 1rem;
        }
    </style>
</head>
<body>
    <main class="page">
        <div class="container policy">
            <a class="policy__back" href="/">&larr; Voltar</a>

            <img class="policy__logo" src="<?= e(safe_url_privacy($site['brand']['logo'] ?? '')) ?>" alt="<?= e($brandName) ?>" width="160" height="86" decoding="async">

            <h1>Política de Privacidade</h1>
            <p>Esta política explica como <strong><?= e($brandName) ?></strong> (controladora dos dados) trata as
            informações enviadas pelo formulário de contato deste site, em conformidade com a Lei Geral de Proteção
            de Dados (Lei nº 13.709/2018 — LGPD).</p>

            <h2>Quais dados coletamos</h2>
            <ul>
                <li>Nome</li>
                <li>E-mail</li>
                <li>Telefone / WhatsApp</li>
                <li>Mensagem enviada no formulário</li>
                <li>Endereço IP e data/hora do envio (registrados junto com o aceite desta política)</li>
            </ul>

            <h2>Para que usamos esses dados</h2>
            <p>Os dados são usados exclusivamente para entrar em contato sobre a lista de espera e o atendimento
            solicitado. Não usamos esses dados para nenhuma outra finalidade.</p>

            <h2>Base legal</h2>
            <p>O tratamento é baseado no seu <strong>consentimento</strong>, manifestado ao marcar a caixa de
            aceite no formulário de contato antes do envio.</p>

            <h2>Por quanto tempo guardamos os dados</h2>
            <p>Os dados ficam armazenados até a solicitação de exclusão.</p>

            <h2>Compartilhamento</h2>
            <p>Não compartilhamos, vendemos ou cedemos esses dados a terceiros. O acesso é restrito ao responsável
            pela mentoria.</p>

            <h2>Segurança</h2>
            <p>Adotamos medidas técnicas para proteger os dados: conexão criptografada (HTTPS), senha de acesso ao
            painel armazenada com hash criptográfico e acesso ao painel restrito por login.</p>

            <h2>Seus direitos</h2>
            <p>Você pode, a qualquer momento, solicitar:</p>
            <ul>
                <li>Acesso aos dados que temos sobre você;</li>
                <li>Correção de dados incompletos ou desatualizados;</li>
                <li>Exclusão dos seus dados;</li>
                <li>Revogação do consentimento dado anteriormente.</li>
            </ul>
            <p>Esses pedidos, inclusive o de exclusão, são feitos mediante solicitação pelo e-mail de contato e
            atendidos manualmente pelo responsável.</p>
            <?php if ($contactEmail !== ''): ?>
            <p>Para exercer esses direitos, entre em contato pelo e-mail
            <a href="mailto:<?= e($contactEmail) ?>"><?= e($contactEmail) ?></a>.</p>
            <?php endif; ?>
        </div>
    </main>

    <footer class="site-footer">
        <div class="container">
            <?php if (!empty($site['lgpd']['footer_notice'])): ?>
                <p class="site-footer__lgpd"><?= e($site['lgpd']['footer_notice']) ?></p>
            <?php endif; ?>
        </div>
    </footer>
</body>
</html>
