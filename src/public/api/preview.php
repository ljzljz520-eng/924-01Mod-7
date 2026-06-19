<?php
require_once __DIR__ . '/../../includes/editor_repo.php';
require_once __DIR__ . '/../../includes/image_renderer.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$templateId = (int) ($input['template_id'] ?? 0);
$customConfig = $input['config'] ?? [];

if (!$templateId) {
    http_response_code(400);
    echo json_encode(['error' => '模板ID不能为空']);
    exit;
}

$template = get_template_with_regions($templateId);
if (!$template || empty($template['source_image'])) {
    http_response_code(404);
    echo json_encode(['error' => '模板不存在或不支持在线编辑']);
    exit;
}

$cleanConfig = [];
foreach ($template['editable_regions'] as $region) {
    if (!$region['is_editable']) {
        continue;
    }
    $key = $region['region_type'] . '_' . $region['id'];
    if (isset($customConfig[$key])) {
        $cleanConfig[$key] = $customConfig[$key];
    }
    if ($region['region_type'] === 'qrcode') {
        $dataKey = 'qrcode_data_' . $region['id'];
        $imageKey = 'qrcode_image_' . $region['id'];
        if (isset($customConfig[$dataKey])) {
            $cleanConfig[$dataKey] = $customConfig[$dataKey];
        }
        if (isset($customConfig[$imageKey])) {
            $cleanConfig[$imageKey] = $customConfig[$imageKey];
        }
    }
}

$order = create_order($templateId, $cleanConfig);

echo json_encode([
    'success' => true,
    'order_id' => $order['id'],
    'preview_token' => $order['preview_token'],
    'download_token' => $order['download_token'],
    'preview_url' => '/api/preview_image.php?token=' . $order['preview_token'],
]);
