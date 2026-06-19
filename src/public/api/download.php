<?php
require_once __DIR__ . '/../../includes/editor_repo.php';
require_once __DIR__ . '/../../includes/image_renderer.php';

$token = $_GET['token'] ?? '';
$action = $_GET['action'] ?? '';

if (empty($token)) {
    http_response_code(400);
    echo json_encode(['error' => '无效的下载令牌']);
    exit;
}

if ($action === 'check') {
    $order = get_order_by_download_token($token);
    header('Content-Type: application/json');
    echo json_encode([
        'available' => $order !== null,
        'status' => $order['status'] ?? 'expired',
    ]);
    exit;
}

$order = get_order_by_download_token($token);
if (!$order) {
    http_response_code(403);
    echo '请先完成购买后再下载高清原图';
    exit;
}

$template = get_template_with_regions($order['template_id']);
if (!$template) {
    http_response_code(404);
    echo '模板不存在';
    exit;
}

$image = render_image($template, $order['custom_config'], false);
if (!$image) {
    http_response_code(500);
    echo '图片生成失败';
    exit;
}

$filename = 'custom_' . $template['id'] . '_' . date('YmdHis') . '.jpg';
header('Content-Type: image/jpeg');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

imagejpeg($image, null, 100);
imagedestroy($image);
