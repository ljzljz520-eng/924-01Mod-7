<?php
require_once __DIR__ . '/../includes/template_repo.php';
require_once __DIR__ . '/../includes/helpers.php';

$keyword = isset($_GET['q']) ? trim($_GET['q']) : null;
$templates = fetch_templates($keyword);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>免费模板下载 | TemplateHub</title>
    <link rel="stylesheet" href="/assets/styles.css">
</head>
<body>
<header>
    <div class="navbar">
        <div class="brand">
            <span class="badge">Free</span>
            <span>TemplateHub · 免费模板下载</span>
        </div>
        <div>
            <a href="/admin/login.php" class="btn btn-ghost" style="padding:10px 14px">后台登录</a>
        </div>
    </div>
    <div class="hero">
        <h1>精选现代化网页模板，免费下载即用</h1>
        <p>面向企业、作品集与内容站点的高质量模板，提供多图预览与下载链接。可在后台增删改，适合快速上线。</p>
        <form class="search-bar" method="get">
            <input type="text" name="q" placeholder="搜索标题或标签，如 企业 / 作品集" value="<?php echo e($keyword); ?>">
            <button type="submit" class="btn btn-primary">搜索模板</button>
            <a class="btn btn-ghost" href="/">重置</a>
        </form>
        <div class="notice">资源永久免费提供下载，若需定制或商业授权可在下载后与站长联系。</div>
    </div>
</header>

<main class="main">
    <div class="grid">
        <?php foreach ($templates as $tpl): $images = format_preview_images($tpl['preview_images']); ?>
            <article class="card">
                <img src="<?php echo e($images[0] ?? 'https://images.unsplash.com/photo-1481277542470-605612bd2d61?auto=format&fit=crop&w=1200&q=80'); ?>" alt="<?php echo e($tpl['title']); ?> 预览图">
                <div class="card-body">
                    <h3><?php echo e($tpl['title']); ?></h3>
                    <p><?php echo e($tpl['description']); ?></p>
                    <div class="tags">
                        <?php foreach (array_filter(array_map('trim', explode(',', $tpl['tags'] ?? ''))) as $tag): ?>
                            <span class="tag"><?php echo e($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-actions">
                        <a class="btn btn-primary" style="flex:1; text-align:center;" href="<?php echo e($tpl['download_url']); ?>" target="_blank" rel="noopener">免费下载</a>
                        <a class="btn btn-ghost" style="flex:1; text-align:center;" href="/detail.php?id=<?php echo e($tpl['id']); ?>">进入详情</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (empty($templates)): ?>
            <p>未找到相关模板，换个关键词试试。</p>
        <?php endif; ?>
    </div>
</main>

<footer class="footer">
    <p>TemplateHub · 免费模板库 · 后台可管理模板与下载链接。</p>
</footer>
</body>
</html>
