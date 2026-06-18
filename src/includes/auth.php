<?php
require_once __DIR__ . '/bootstrap.php';

function ensure_default_admin(): void
{
    global $config;
    $pdo = db();
    $stmt = $pdo->query('SELECT COUNT(*) AS cnt FROM admins');
    $count = (int) $stmt->fetch()['cnt'];

    if ($count === 0) {
        $hash = password_hash($config['admin']['password'], PASSWORD_BCRYPT);
        $insert = $pdo->prepare('INSERT INTO admins (username, password_hash) VALUES (:u, :p)');
        $insert->execute([
            ':u' => $config['admin']['username'],
            ':p' => $hash,
        ]);
    }
}

function login(string $username, string $password): bool
{
    ensure_default_admin();
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM admins WHERE username = :u LIMIT 1');
    $stmt->execute([':u' => $username]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        $_SESSION['admin_id'] = $admin['id'];
        $_SESSION['admin_username'] = $admin['username'];
        return true;
    }

    return false;
}

function require_login(): void
{
    if (!is_logged_in()) {
        header('Location: /admin/login.php');
        exit;
    }
}

function is_logged_in(): bool
{
    return isset($_SESSION['admin_id']);
}

function logout(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }
    session_destroy();
}
