<?php
require_once __DIR__ . '/../includes/template_repo.php';
require_once __DIR__ . '/../includes/helpers.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$template = $id ? get_template($id) : null;

if (!$template) {
    header('Location: /');
    exit;
}

$images = format_preview_images($template['preview_images']);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($template['title']); ?> - 免费模板下载 | TemplateHub</title>
    <link rel="stylesheet" href="/assets/styles.css">
    <style>
        .detail-wrap {max-width: 1000px;margin:40px auto;padding:0 24px;}
        .back-link {display:inline-block;margin-bottom:16px;color:var(--accent);font-weight:600;}
        .detail-header {display:grid;gap:16px;margin-bottom:24px;}
        .detail-header h1 {margin:0;font-size:2rem;letter-spacing:-0.02em;}
        .detail-meta {display:flex;gap:12px;flex-wrap:wrap;align-items:center;}
        .detail-gallery {display:grid;gap:16px;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));margin-bottom:32px;}
        .gallery-item {border-radius:var(--radius);overflow:hidden;border:1px solid var(--border);box-shadow:var(--shadow);transition:transform 0.2s;}
        .gallery-item:hover {transform:scale(1.02);}
        .gallery-item img {width:100%;height:240px;object-fit:cover;display:block;}
        .detail-section {background:#fff;border:1px solid var(--border);border-radius:var(--radius);padding:24px;margin-bottom:20px;box-shadow:var(--shadow);}
        .detail-section h2 {margin:0 0 12px;font-size:1.3rem;color:var(--text);}
        .detail-section p {margin:0;line-height:1.7;color:var(--muted);}
        .download-box {background:linear-gradient(135deg,rgba(37,99,235,0.05),rgba(16,185,129,0.05));border:2px solid var(--accent);border-radius:var(--radius);padding:20px;display:flex;flex-direction:column;gap:12px;align-items:center;}
        .download-box .url {word-break:break-all;color:var(--muted);font-size:0.95rem;text-align:center;}
        @media (max-width:640px){.detail-gallery{grid-template-columns:1fr;}}
    </style>
</head>
<body>
<div class="detail-wrap">
    <a href="/" class="back-link">← 返回模板列表</a>

    <div class="detail-header">
        <h1><?php echo e($template['title']); ?></h1>
        <div class="detail-meta">
            <div class="tags">
                <?php foreach (array_filter(array_map('trim', explode(',', $template['tags'] ?? ''))) as $tag): ?>
                    <span class="tag"><?php echo e($tag); ?></span>
                <?php endforeach; ?>
            </div>
            <span class="muted" style="margin-left:auto;">更新于 <?php echo e($template['updated_at']); ?></span>
        </div>
    </div>

    <?php if (!empty($images)): ?>
        <div class="detail-gallery">
            <?php foreach ($images as $img): ?>
                <?php if ($img): ?>
                    <a href="<?php echo e($img); ?>" target="_blank" rel="noopener" class="gallery-item">
                        <img src="<?php echo e($img); ?>" alt="<?php echo e($template['title']); ?> 预览图" loading="lazy">
                    </a>
                <?php endif; ?>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <?php if ($template['description']): ?>
        <div class="detail-section">
            <h2>简要描述</h2>
            <p><?php echo e($template['description']); ?></p>
        </div>
    <?php endif; ?>

    <?php if ($template['article']): ?>
        <div class="detail-section">
            <h2>详细说明</h2>
            <p style="white-space:pre-wrap;"><?php echo e($template['article']); ?></p>
        </div>
    <?php endif; ?>

    <div class="detail-section download-box">
        <h2 style="margin:0;">免费下载此模板</h2>
        <div class="url"><?php echo e($template['download_url']); ?></div>
        <a class="btn btn-primary" href="<?php echo e($template['download_url']); ?>" target="_blank" rel="noopener" style="padding:14px 32px;font-size:1.1rem;">立即下载</a>
        <p class="muted" style="margin:0;font-size:0.9rem;">资源永久免费，如需定制可联系站长</p>
    </div>

    <div style="text-align:center;margin-top:32px;">
        <a href="/" class="btn btn-ghost">← 返回首页</a>
    </div>
</div>

<footer class="footer">
    <p>TemplateHub · 免费模板库 · 后台可管理模板与下载链接。</p>
</footer>
</body>
</html>
