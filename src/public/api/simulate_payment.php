<?php
require_once __DIR__ . '/../../includes/editor_repo.php';
require_once __DIR__ . '/../../includes/helpers.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$orderId = (int) ($input['order_id'] ?? 0);

if (!$orderId) {
    http_response_code(400);
    echo json_encode(['error' => '订单ID不能为空']);
    exit;
}

try {
    mark_order_paid($orderId);
    echo json_encode([
        'success' => true,
        'message' => '支付成功，可以下载高清原图了',
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['error' => '支付失败: ' . $e->getMessage()]);
}
