<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/helpers.php';

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = trim($_POST['username'] ?? '');
    $password = trim($_POST['password'] ?? '');
    if (login($username, $password)) {
        header('Location: /admin/dashboard.php');
        exit;
    }
    $error = '账号或密码不正确。';
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>后台登录</title>
    <link rel="stylesheet" href="/assets/styles.css">
    <style>
        .auth-box {max-width: 420px;margin: 120px auto;background:#fff;border:1px solid #e2e8f0;border-radius:16px;box-shadow:0 18px 40px rgba(15,23,42,0.14);padding:28px;}
        .auth-box h2 {margin:0 0 16px;}
        .auth-box form {display:flex;flex-direction:column;gap:12px;}
        .auth-box input {padding:12px 14px;border:1px solid #e2e8f0;border-radius:12px;font-size:1rem;}
        .error {color:#b91c1c;background:rgba(248,113,113,0.12);border:1px solid rgba(248,113,113,0.3);padding:10px 12px;border-radius:12px;}
        .hint {color:#475569;font-size:0.95rem;margin-top:6px;}
    </style>
</head>
<body>
<div class="auth-box">
    <h2>管理后台登录</h2>
    <?php if ($error): ?>
        <div class="error"><?php echo e($error); ?></div>
    <?php endif; ?>
    <form method="post">
        <input name="username" placeholder="账号" required>
        <input name="password" type="password" placeholder="密码" required>
        <button class="btn btn-primary" type="submit">登录</button>
    </form>
    <div class="hint">默认账号/密码来源于环境变量：ADMIN_DEFAULT_USER / ADMIN_DEFAULT_PASS。</div>
</div>
</body>
</html>
