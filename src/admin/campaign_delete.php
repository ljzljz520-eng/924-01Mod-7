<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/campaign_repo.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id > 0) {
        delete_campaign($id);
    }
}

header('Location: /admin/campaigns.php');
exit;
