<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/template_repo.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $id = (int) ($_POST['id'] ?? 0);
    if ($id) {
        delete_template($id);
    }
}

header('Location: /admin/dashboard.php');
exit;
