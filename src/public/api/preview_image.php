<?php
require_once __DIR__ . '/../../includes/editor_repo.php';
require_once __DIR__ . '/../../includes/image_renderer.php';

$token = $_GET['token'] ?? '';

if (empty($token)) {
    http_response_code(400);
    echo '无效的预览令牌';
    exit;
}

$order = get_order_by_preview_token($token);
if (!$order) {
    http_response_code(404);
    echo '预览已过期或不存在';
    exit;
}

$template = get_template_with_regions($order['template_id']);
if (!$template) {
    http_response_code(404);
    echo '模板不存在';
    exit;
}

$image = render_image($template, $order['custom_config'], true);
if (!$image) {
    http_response_code(500);
    echo '图片生成失败';
    exit;
}

output_image($image, 'jpg', 80);
