<?php
require_once __DIR__ . '/bootstrap.php';

function fetch_templates(?string $keyword = null): array
{
    $pdo = db();
    if ($keyword) {
        $like = '%' . $keyword . '%';
        $stmt = $pdo->prepare('SELECT * FROM templates WHERE title LIKE :k OR tags LIKE :k ORDER BY created_at DESC');
        $stmt->execute([':k' => $like]);
    } else {
        $stmt = $pdo->query('SELECT * FROM templates ORDER BY created_at DESC');
    }
    return $stmt->fetchAll();
}

function get_template(int $id): ?array
{
    $pdo = db();
    $stmt = $pdo->prepare('SELECT * FROM templates WHERE id = :id');
    $stmt->execute([':id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function upsert_template(array $data, ?int $id = null): void
{
    $pdo = db();
    $images = array_values(array_filter(array_map('trim', $data['preview_images'] ?? [])));
    $imagesJson = json_encode($images, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

    if ($id === null) {
        $stmt = $pdo->prepare('INSERT INTO templates (title, description, article, preview_images, download_url, tags) VALUES (:t, :d, :a, :p, :u, :g)');
        $stmt->execute([
            ':t' => $data['title'] ?? '',
            ':d' => $data['description'] ?? '',
            ':a' => $data['article'] ?? '',
            ':p' => $imagesJson,
            ':u' => $data['download_url'] ?? '',
            ':g' => $data['tags'] ?? '',
        ]);
    } else {
        $stmt = $pdo->prepare('UPDATE templates SET title=:t, description=:d, article=:a, preview_images=:p, download_url=:u, tags=:g WHERE id=:id');
        $stmt->execute([
            ':t' => $data['title'] ?? '',
            ':d' => $data['description'] ?? '',
            ':a' => $data['article'] ?? '',
            ':p' => $imagesJson,
            ':u' => $data['download_url'] ?? '',
            ':g' => $data['tags'] ?? '',
            ':id' => $id,
        ]);
    }
}

function delete_template(int $id): void
{
    $pdo = db();
    $stmt = $pdo->prepare('DELETE FROM templates WHERE id = :id');
    $stmt->execute([':id' => $id]);
}

function format_preview_images(?string $json): array
{
    if (!$json) {
        return [];
    }
    $decoded = json_decode($json, true);
    return is_array($decoded) ? $decoded : [];
}
