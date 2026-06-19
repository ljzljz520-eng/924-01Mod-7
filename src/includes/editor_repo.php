<?php
require_once __DIR__ . '/bootstrap.php';
require_once __DIR__ . '/template_repo.php';

function get_editable_regions(int $template_id): array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM editable_regions WHERE template_id = :tid ORDER BY sort_order ASC, id ASC');
    $stmt->execute([':tid' => $template_id]);
    $regions = $stmt->fetchAll();
    
    foreach ($regions as &$region) {
        $region['config'] = json_decode($region['config'], true) ?: [];
    }
    return $regions;
}

function get_editable_region(int $id): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM editable_regions WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $region = $stmt->fetch();
    if ($region) {
        $region['config'] = json_decode($region['config'], true) ?: [];
    }
    return $region;
}

function upsert_editable_region(int $template_id, array $data, ?int $id = null): void
{
    $pdo = db();
    $configJson = json_encode($data['config'] ?? [], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    
    if ($id === null) {
        $stmt = $pdo->prepare('INSERT INTO editable_regions (template_id, region_type, region_name, config, is_editable, sort_order) VALUES (:tid, :type, :name, :cfg, :editable, :ord)');
        $stmt->execute([
            ':tid' => $template_id,
            ':type' => $data['region_type'] ?? 'text',
            ':name' => $data['region_name'] ?? '',
            ':cfg' => $configJson,
            ':editable' => $data['is_editable'] ?? 1,
            ':ord' => $data['sort_order'] ?? 0,
        ]);
    } else {
        $stmt = $pdo->prepare('UPDATE editable_regions SET region_type=:type, region_name=:name, config=:cfg, is_editable=:editable, sort_order=:ord WHERE id=:id');
        $stmt->execute([
            ':type' => $data['region_type'] ?? 'text',
            ':name' => $data['region_name'] ?? '',
            ':cfg' => $configJson,
            ':editable' => $data['is_editable'] ?? 1,
            ':ord' => $data['sort_order'] ?? 0,
            ':id' => $id,
        ]);
    }
}

function delete_editable_region(int $id): void
{
    $pdo = db();
    $stmt = $pdo->prepare('DELETE FROM editable_regions WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function create_order(int $template_id, array $custom_config): array
{
    $pdo = db();
    $configJson = json_encode($custom_config, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    $previewToken = bin2hex(random_bytes(16));
    $downloadToken = bin2hex(random_bytes(20));
    
    $stmt = $pdo->prepare('INSERT INTO orders (template_id, custom_config, preview_token, download_token, status, expires_at) VALUES (:tid, :cfg, :ptoken, :dtoken, :status, DATE_ADD(NOW(), INTERVAL 24 HOUR))');
    $stmt->execute([
        ':tid' => $template_id,
        ':cfg' => $configJson,
        ':ptoken' => $previewToken,
        ':dtoken' => $downloadToken,
        ':status' => 'pending',
    ]);
    
    $orderId = $pdo->lastInsertId();
    return [
        'id' => $orderId,
        'preview_token' => $previewToken,
        'download_token' => $downloadToken,
    ];
}

function get_order_by_preview_token(string $token): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE preview_token = :token AND expires_at > NOW()');
    $stmt->execute([':token' => $token]);
    $order = $stmt->fetch();
    if ($order) {
        $order['custom_config'] = json_decode($order['custom_config'], true) ?: [];
    }
    return $order;
}

function get_order_by_download_token(string $token): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM orders WHERE download_token = :token AND status = "paid" AND expires_at > NOW()');
    $stmt->execute([':token' => $token]);
    $order = $stmt->fetch();
    if ($order) {
        $order['custom_config'] = json_decode($order['custom_config'], true) ?: [];
    }
    return $order;
}

function mark_order_paid(int $order_id): void
{
    $pdo = db();
    $stmt = $pdo->prepare('UPDATE orders SET status = "paid" WHERE id = :id');
    $stmt->execute([':id' => $order_id]);
}

function get_template_with_regions(int $id): ?array
{
    $template = get_template($id);
    if (!$template) {
        return null;
    }
    $template['editable_regions'] = get_editable_regions($id);
    $template['preview_images_array'] = format_preview_images($template['preview_images']);
    return $template;
}
