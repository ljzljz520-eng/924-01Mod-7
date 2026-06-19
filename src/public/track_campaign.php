<?php
require_once __DIR__ . '/../includes/campaign_repo.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Method not allowed']);
    exit;
}

$campaign_id = isset($_POST['campaign_id']) ? (int) $_POST['campaign_id'] : 0;
$template_id = isset($_POST['template_id']) ? (int) $_POST['template_id'] : null;
$type = isset($_POST['type']) ? trim($_POST['type']) : '';

if ($campaign_id <= 0 || !in_array($type, ['click', 'download', 'view'])) {
    echo json_encode(['success' => false, 'error' => 'Invalid parameters']);
    exit;
}

$campaign = get_campaign($campaign_id);
if (!$campaign || !is_campaign_active($campaign)) {
    echo json_encode(['success' => false, 'error' => 'Campaign not active']);
    exit;
}

try {
    record_campaign_stat($campaign_id, $template_id, $type);
    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
