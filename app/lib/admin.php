<?php

const LEAD_STATUSES = ['novo', 'contatado', 'fechado'];

// Neutraliza CSV/formula injection: células que começam com = + - @ TAB ou CR
// ganham um prefixo ' para o Excel/LibreOffice tratá-las como texto puro.
function csv_safe(?string $value): string
{
    $value = (string) $value;

    if ($value !== '' && in_array($value[0], ['=', '+', '-', '@', "\t", "\r"], true)) {
        return "'" . $value;
    }

    return $value;
}

function admin_kpis(PDO $pdo): array
{
    $total = (int) $pdo->query('SELECT COUNT(*) FROM leads')->fetchColumn();

    $weekStmt = $pdo->prepare('SELECT COUNT(*) FROM leads WHERE created_at >= ?');
    $weekStmt->execute([date('Y-m-d H:i:s', time() - 7 * 86400)]);
    $week = (int) $weekStmt->fetchColumn();

    $todayStmt = $pdo->prepare('SELECT COUNT(*) FROM leads WHERE created_at >= ?');
    $todayStmt->execute([date('Y-m-d 00:00:00')]);
    $today = (int) $todayStmt->fetchColumn();

    return ['total' => $total, 'week' => $week, 'today' => $today];
}

// Sem produtos cadastrados (site.php com 'products' vazio), o radar mostra as
// demandas mais recentes escritas pelos leads; com produtos, agrega por título.
function admin_radar(PDO $pdo, array $site): array
{
    $products = $site['products'] ?? [];

    if ($products !== []) {
        $titles = [];
        foreach ($products as $product) {
            $titles[(string) $product['id']] = (string) $product['title'];
        }

        $counts = [];
        $stmt = $pdo->query("SELECT interests FROM leads WHERE interests IS NOT NULL AND interests <> ''");
        foreach ($stmt->fetchAll() as $row) {
            $interests = json_decode((string) $row['interests'], true);
            if (!is_array($interests)) {
                continue;
            }
            foreach ($interests as $interest) {
                $id = (string) ($interest['id'] ?? '');
                $title = $titles[$id] ?? (string) ($interest['title'] ?? '');
                if ($title === '') {
                    continue;
                }
                $counts[$title] = ($counts[$title] ?? 0) + 1;
            }
        }
        arsort($counts);

        $items = [];
        foreach ($counts as $title => $count) {
            $items[] = ['label' => $title, 'count' => $count];
        }

        return ['mode' => 'products', 'items' => $items];
    }

    $stmt = $pdo->prepare(
        "SELECT name, message, created_at FROM leads WHERE message <> '' ORDER BY created_at DESC, id DESC LIMIT 5"
    );
    $stmt->execute();

    $items = [];
    foreach ($stmt->fetchAll() as $row) {
        $items[] = [
            'name' => $row['name'],
            'message' => mb_substr(trim((string) $row['message']), 0, 120),
            'created_at' => $row['created_at'],
        ];
    }

    return ['mode' => 'messages', 'items' => $items];
}

function admin_leads(PDO $pdo, string $q, int $limit = 200): array
{
    $q = mb_substr(trim($q), 0, 100);

    $sql = 'SELECT id, name, phone, email, message, interests, status, created_at FROM leads';
    $params = [];

    if ($q !== '') {
        $escaped = str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q);
        $like = '%' . $escaped . '%';
        $sql .= " WHERE name LIKE ? ESCAPE '\\\\' OR email LIKE ? ESCAPE '\\\\' OR message LIKE ? ESCAPE '\\\\'";
        $params = [$like, $like, $like];
    }

    $sql .= ' ORDER BY created_at DESC, id DESC LIMIT ' . (int) $limit;

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);

    return $stmt->fetchAll();
}

function format_phone(string $phone): string
{
    $digits = preg_replace('/\D+/', '', $phone);
    $len = strlen($digits);

    if ($len === 11) {
        return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 5), substr($digits, 7));
    }

    if ($len === 10) {
        return sprintf('(%s) %s-%s', substr($digits, 0, 2), substr($digits, 2, 4), substr($digits, 6));
    }

    return $phone;
}
