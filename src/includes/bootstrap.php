<?php
$config = require __DIR__ . '/../config.php';

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if ($config['env'] === 'development') {
    error_reporting(E_ALL);
    ini_set('display_errors', 1);
}

function db(): PDO
{
    static $pdo = null;
    global $config;

    if ($pdo === null) {
        $dsn = sprintf(
            'mysql:host=%s;port=%s;dbname=%s;charset=utf8mb4',
            $config['db']['host'],
            $config['db']['port'],
            $config['db']['name']
        );
        $pdo = new PDO($dsn, $config['db']['user'], $config['db']['pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }

    return $pdo;
}
