<?php
require_once __DIR__ . '/bootstrap.php';

function fetch_campaigns(?bool $active_only = false): array
{
    $pdo = db();
    if ($active_only) {
        $today = date('Y-m-d');
        $stmt = $pdo->prepare('SELECT * FROM campaigns WHERE status = 1 AND start_date <= :today AND end_date >= :today ORDER BY sort_order ASC, id DESC');
        $stmt->execute([':today' => $today]);
    } else {
        $stmt = $pdo->query('SELECT * FROM campaigns ORDER BY sort_order ASC, id DESC');
    }
    return $stmt->fetchAll();
}

function get_campaign(int $id): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM campaigns WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function get_campaign_by_slug(string $slug): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM campaigns WHERE slug = :slug LIMIT 1');
    $stmt->execute([':slug' => $slug]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function is_campaign_active(array $campaign): bool
{
    if (!$campaign || (int) $campaign['status'] !== 1) {
        return false;
    }
    $today = date('Y-m-d');
    return $campaign['start_date'] <= $today && $campaign['end_date'] >= $today;
}

function upsert_campaign(array $data, ?int $id = null): void
{
    $pdo = db();
    $slug = !empty($data['slug']) ? $data['slug'] : generate_campaign_slug($data['name'] ?? '');
    $sort_order = (int) ($data['sort_order'] ?? 0);
    $status = (int) ($data['status'] ?? 1);

    if ($id === null) {
        $stmt = $pdo->prepare('INSERT INTO campaigns (name, slug, description, cover_image, theme_color, start_date, end_date, sort_order, status) VALUES (:n, :s, :d, :c, :t, :sd, :ed, :so, :st)');
        $stmt->execute([
            ':n' => $data['name'] ?? '',
            ':s' => $slug,
            ':d' => $data['description'] ?? '',
            ':c' => $data['cover_image'] ?? '',
            ':t' => $data['theme_color'] ?? '#2563eb',
            ':sd' => $data['start_date'] ?? date('Y-m-d'),
            ':ed' => $data['end_date'] ?? date('Y-m-d', strtotime('+30 days')),
            ':so' => $sort_order,
            ':st' => $status,
        ]);
    } else {
        $stmt = $pdo->prepare('UPDATE campaigns SET name=:n, slug=:s, description=:d, cover_image=:c, theme_color=:t, start_date=:sd, end_date=:ed, sort_order=:so, status=:st WHERE id=:id');
        $stmt->execute([
            ':n' => $data['name'] ?? '',
            ':s' => $slug,
            ':d' => $data['description'] ?? '',
            ':c' => $data['cover_image'] ?? '',
            ':t' => $data['theme_color'] ?? '#2563eb',
            ':sd' => $data['start_date'] ?? date('Y-m-d'),
            ':ed' => $data['end_date'] ?? date('Y-m-d', strtotime('+30 days')),
            ':so' => $sort_order,
            ':st' => $status,
            ':id' => $id,
        ]);
    }
}

function generate_campaign_slug(string $name): string
{
    $slug = strtolower(trim(preg_replace('/[^a-zA-Z0-9\x{4e00}-\x{9fa5}]+/u', '-', $name), '-'));
    $slug = substr($slug, 0, 80);
    if (empty($slug)) {
        $slug = 'campaign-' . time();
    }
    $pdo = db();
    $base_slug = $slug;
    $counter = 1;
    while (true) {
        $stmt = $pdo->prepare('SELECT id FROM campaigns WHERE slug = :slug LIMIT 1');
        $stmt->execute([':slug' => $slug]);
        if (!$stmt->fetch()) {
            break;
        }
        $slug = $base_slug . '-' . $counter;
        $counter++;
    }
    return $slug;
}

function delete_campaign(int $id): void
{
    $pdo = db();
    $stmt = $pdo->prepare('DELETE FROM campaigns WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function get_campaign_templates(int $campaign_id): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT t.*, ct.sort_order AS campaign_sort_order FROM campaign_templates ct JOIN templates t ON ct.template_id = t.id WHERE ct.campaign_id = :cid ORDER BY ct.sort_order ASC, ct.id ASC');
    $stmt->execute([':cid' => $campaign_id]);
    return $stmt->fetchAll();
}

function get_campaign_template_ids(int $campaign_id): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT template_id, sort_order FROM campaign_templates WHERE campaign_id = :cid ORDER BY sort_order ASC, id ASC');
    $stmt->execute([':cid' => $campaign_id]);
    $rows = $stmt->fetchAll();
    $result = [];
    foreach ($rows as $row) {
        $result[] = (int) $row['template_id'];
    }
    return $result;
}

function set_campaign_templates(int $campaign_id, array $template_ids): void
{
    $pdo = db();
    $pdo->beginTransaction();
    try {
        $stmt = $pdo->prepare('DELETE FROM campaign_templates WHERE campaign_id = :cid');
        $stmt->execute([':cid' => $campaign_id]);

        if (!empty($template_ids)) {
            $sort_order = 0;
            foreach ($template_ids as $template_id) {
                $template_id = (int) $template_id;
                if ($template_id > 0) {
                    $stmt = $pdo->prepare('INSERT INTO campaign_templates (campaign_id, template_id, sort_order) VALUES (:cid, :tid, :so)');
                    $stmt->execute([
                        ':cid' => $campaign_id,
                        ':tid' => $template_id,
                        ':so' => $sort_order,
                    ]);
                    $sort_order++;
                }
            }
        }

        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        throw $e;
    }
}

function record_campaign_stat(int $campaign_id, ?int $template_id, string $type): void
{
    $pdo = db();
    $today = date('Y-m-d');
    $template_id = $template_id ?? null;

    $column = '';
    switch ($type) {
        case 'click':
            $column = 'click_count';
            break;
        case 'download':
            $column = 'download_count';
            break;
        case 'view':
            $column = 'view_count';
            break;
        default:
            return;
    }

    $stmt = $pdo->prepare('INSERT INTO campaign_stats (campaign_id, template_id, stat_date, ' . $column . ') VALUES (:cid, :tid, :d, 1) ON DUPLICATE KEY UPDATE ' . $column . ' = ' . $column . ' + 1');
    $stmt->execute([
        ':cid' => $campaign_id,
        ':tid' => $template_id,
        ':d' => $today,
    ]);
}

function get_campaign_stats(int $campaign_id, ?string $start_date = null, ?string $end_date = null): array
{
    $pdo = db();
    $sql = 'SELECT SUM(click_count) AS total_clicks, SUM(download_count) AS total_downloads, SUM(view_count) AS total_views FROM campaign_stats WHERE campaign_id = :cid';
    $params = [':cid' => $campaign_id];

    if ($start_date) {
        $sql .= ' AND stat_date >= :sd';
        $params[':sd'] = $start_date;
    }
    if ($end_date) {
        $sql .= ' AND stat_date <= :ed';
        $params[':ed'] = $end_date;
    }

    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $result = $stmt->fetch();

    return [
        'total_clicks' => (int) ($result['total_clicks'] ?? 0),
        'total_downloads' => (int) ($result['total_downloads'] ?? 0),
        'total_views' => (int) ($result['total_views'] ?? 0),
    ];
}

function get_campaign_template_stats(int $campaign_id): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT template_id, SUM(click_count) AS total_clicks, SUM(download_count) AS total_downloads, SUM(view_count) AS total_views FROM campaign_stats WHERE campaign_id = :cid AND template_id IS NOT NULL GROUP BY template_id');
    $stmt->execute([':cid' => $campaign_id]);
    return $stmt->fetchAll();
}

function get_campaign_daily_stats(int $campaign_id, int $days = 30): array
{
    $pdo = db();
    $start_date = date('Y-m-d', strtotime('-' . ($days - 1) . ' days'));
    $stmt = $pdo->prepare('SELECT stat_date, SUM(click_count) AS clicks, SUM(download_count) AS downloads, SUM(view_count) AS views FROM campaign_stats WHERE campaign_id = :cid AND stat_date >= :sd GROUP BY stat_date ORDER BY stat_date ASC');
    $stmt->execute([
        ':cid' => $campaign_id,
        ':sd' => $start_date,
    ]);
    return $stmt->fetchAll();
}

function get_campaign_status_label(array $campaign): string
{
    if ((int) $campaign['status'] !== 1) {
        return '已下架';
    }
    $today = date('Y-m-d');
    if ($campaign['start_date'] > $today) {
        return '待上线';
    }
    if ($campaign['end_date'] < $today) {
        return '已过期';
    }
    return '进行中';
}
