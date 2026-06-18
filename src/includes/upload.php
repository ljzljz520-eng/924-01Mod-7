<?php
function handle_file_upload(array $file, string $category = 'images'): ?string
{
    if (!isset($file['error']) || $file['error'] !== UPLOAD_ERR_OK) {
        return null;
    }

    $allowed = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'zip', 'rar', '7z', 'tar', 'gz'];
    $max_size = 50 * 1024 * 1024; // 50MB

    if ($file['size'] > $max_size) {
        return null;
    }

    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed)) {
        return null;
    }

    $upload_dir = __DIR__ . '/../public/uploads/' . $category;
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
    }

    $filename = uniqid() . '_' . time() . '.' . $ext;
    $target = $upload_dir . '/' . $filename;

    if (move_uploaded_file($file['tmp_name'], $target)) {
        return '/uploads/' . $category . '/' . $filename;
    }

    return null;
}

function delete_upload(string $path): bool
{
    if (empty($path) || strpos($path, '/uploads/') !== 0) {
        return false;
    }

    $file = __DIR__ . '/../public' . $path;
    if (file_exists($file) && is_file($file)) {
        return unlink($file);
    }

    return false;
}
