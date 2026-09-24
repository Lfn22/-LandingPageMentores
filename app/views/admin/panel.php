<!DOCTYPE html>
<html lang="pt-BR">
<head>
<meta charset="utf-8">
<meta name="robots" content="noindex, nofollow">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Painel administrativo</title>
<link rel="preconnect" href="https://fonts.googleapis.com">
<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
<link href="https://fonts.googleapis.com/css2?family=Archivo:wght@600;700&family=Inter:wght@400;600&display=swap" rel="stylesheet">
<link rel="stylesheet" href="../assets/css/admin.css">
</head>
<body class="admin-body">
<main class="admin-wrap">
    <span class="admin-pill">Painel administrativo</span>

    <section class="admin-card">
        <header class="admin-card__header">
            <h1>Painel administrativo</h1>
            <div class="admin-card__actions">
                <button type="button" class="btn-outline" data-copy-csv>Copiar CSV</button>
                <a class="btn-outline" href="export.php">Baixar CSV</a>
                <form method="post" action="logout.php">
                    <input type="hidden" name="csrf_token" value="<?= e($csrfLogout) ?>">
                    <button type="submit" class="btn-outline">Sair</button>
                </form>
            </div>
        </header>

        <div class="admin-kpis">
            <div class="admin-kpi">
                <span class="admin-kpi__value"><?= (int) $kpis['total'] ?></span>
                <span class="admin-kpi__label">Inscritos no total</span>
            </div>
            <div class="admin-kpi">
                <span class="admin-kpi__value"><?= (int) $kpis['week'] ?></span>
                <span class="admin-kpi__label">Novos nos últimos 7 dias</span>
            </div>
            <div class="admin-kpi">
                <span class="admin-kpi__value"><?= (int) $kpis['today'] ?></span>
                <span class="admin-kpi__label">Inscritos hoje</span>
            </div>
        </div>

        <section class="admin-radar">
            <h2>Radar de demanda</h2>
            <?php if (empty($radar['items'])): ?>
                <p class="admin-empty">Sem demandas registradas ainda.</p>
            <?php elseif ($radar['mode'] === 'products'): ?>
                <ul class="admin-radar__list">
                    <?php foreach ($radar['items'] as $item): ?>
                        <li><span><?= e($item['label']) ?></span><span><?= (int) $item['count'] ?></span></li>
                    <?php endforeach; ?>
                </ul>
            <?php else: ?>
                <ul class="admin-radar__list admin-radar__list--messages">
                    <?php foreach ($radar['items'] as $item): ?>
                        <li>
                            <span class="admin-radar__name"><?= e($item['name']) ?></span>
                            <span class="admin-radar__date"><?= e(date('d/m/Y H:i', strtotime($item['created_at']))) ?></span>
                            <span class="admin-radar__message"><?= e($item['message']) ?></span>
                        </li>
                    <?php endforeach; ?>
                </ul>
            <?php endif; ?>
        </section>

        <section class="admin-queue">
            <h2>Fila de trabalho</h2>
            <form method="get" class="admin-search">
                <input type="search" name="q" value="<?= e($q) ?>" placeholder="Buscar por nome, email ou demanda">
                <button type="submit">Buscar</button>
            </form>

            <?php if (empty($leads)): ?>
                <p class="admin-empty">Nenhum lead encontrado.</p>
            <?php else: ?>
                <div class="admin-table-wrap">
                    <table class="admin-table">
                        <thead>
                            <tr>
                                <th>Nome</th>
                                <th>Contato</th>
                                <th>Demandas</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php $statusLabels = ['novo' => 'Novo', 'contatado' => 'Contatado', 'fechado' => 'Fechado']; ?>
                            <?php foreach ($leads as $lead): ?>
                                <?php
                                    $interestTitles = [];
                                    if (!empty($lead['interests'])) {
                                        $decoded = json_decode((string) $lead['interests'], true);
                                        if (is_array($decoded)) {
                                            foreach ($decoded as $interest) {
                                                $interestTitles[] = (string) ($interest['title'] ?? '');
                                            }
                                        }
                                    }
                                    $messagePreview = mb_substr(trim((string) $lead['message']), 0, 120);
                                ?>
                                <tr>
                                    <td>
                                        <?= e($lead['name']) ?>
                                        <div class="admin-table__meta"><?= e(date('d/m/Y H:i', strtotime($lead['created_at']))) ?></div>
                                        <details class="admin-table__details">
                                            <summary>Mensagem</summary>
                                            <p><?= e($lead['message']) ?></p>
                                        </details>
                                    </td>
                                    <td>
                                        <a href="tel:+55<?= e($lead['phone']) ?>"><?= e(format_phone($lead['phone'])) ?></a><br>
                                        <a href="mailto:<?= e($lead['email']) ?>"><?= e($lead['email']) ?></a><br>
                                        <a href="https://wa.me/55<?= e($lead['phone']) ?>" target="_blank" rel="noopener">WhatsApp</a>
                                    </td>
                                    <td>
                                        <?php if ($interestTitles !== []): ?>
                                            <div><?= e(implode(', ', $interestTitles)) ?></div>
                                        <?php endif; ?>
                                        <?= e($messagePreview) ?>
                                    </td>
                                    <td>
                                        <form method="post" action="status.php" class="admin-status-form" data-status-form>
                                            <input type="hidden" name="csrf_token" value="<?= e($csrfLogout) ?>">
                                            <input type="hidden" name="id" value="<?= (int) $lead['id'] ?>">
                                            <select name="status" data-status-select>
                                                <?php foreach ($statusLabels as $value => $label): ?>
                                                    <option value="<?= e($value) ?>" <?= $value === $lead['status'] ? 'selected' : '' ?>><?= e($label) ?></option>
                                                <?php endforeach; ?>
                                            </select>
                                            <button type="submit" class="btn-outline" data-status-save>Salvar</button>
                                            <span class="admin-status-feedback" data-status-feedback aria-live="polite"></span>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($leads) >= 200): ?>
                    <p class="admin-note">Mostrando os 200 mais recentes.</p>
                <?php endif; ?>
            <?php endif; ?>
        </section>
    </section>

    <footer class="admin-footer"><?= e($site['lgpd']['footer_notice'] ?? '') ?></footer>
</main>
<script src="../assets/js/admin.js" defer></script>
</body>
</html>
