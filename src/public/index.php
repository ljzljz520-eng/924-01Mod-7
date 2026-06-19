<?php
require_once __DIR__ . '/../includes/template_repo.php';
require_once __DIR__ . '/../includes/editor_repo.php';
require_once __DIR__ . '/../includes/campaign_repo.php';
require_once __DIR__ . '/../includes/helpers.php';

$keyword = isset($_GET['q']) ? trim($_GET['q']) : null;
$templates = fetch_templates($keyword);
$active_campaigns = fetch_campaigns(true);

foreach ($templates as &$tpl) {
    $tpl['editable_regions'] = get_editable_regions($tpl['id']);
    $tpl['is_editable'] = !empty($tpl['source_image']) && !empty(array_filter($tpl['editable_regions'], fn($r) => $r['is_editable']));
}
unset($tpl);
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

<?php if (!empty($active_campaigns)): ?>
<section class="campaign-section">
    <div class="campaign-header">
        <h2 class="campaign-title">🎁 限时活动专题</h2>
        <p class="campaign-subtitle">精选节日专属模板，限时呈现</p>
    </div>
    <div class="campaign-grid">
        <?php foreach ($active_campaigns as $camp): ?>
            <?php
                $days_left = floor((strtotime($camp['end_date']) - time()) / 86400);
                $camp_templates = get_campaign_templates($camp['id']);
                $cover = $camp['cover_image'] ?: 'https://images.unsplash.com/photo-1513151233558-d860c5398176?auto=format&fit=crop&w=800&q=80';
            ?>
            <a href="/campaign.php?slug=<?php echo e($camp['slug']); ?>" class="campaign-card" style="--theme-color: <?php echo e($camp['theme_color']); ?>;">
                <div class="campaign-cover">
                    <img src="<?php echo e($cover); ?>" alt="<?php echo e($camp['name']); ?>">
                    <div class="campaign-overlay"></div>
                    <div class="campaign-badge">
                        <?php if ($days_left > 0): ?>
                            仅剩 <?php echo e($days_left); ?> 天
                        <?php else: ?>
                            今天最后一天
                        <?php endif; ?>
                    </div>
                </div>
                <div class="campaign-body">
                    <h3><?php echo e($camp['name']); ?></h3>
                    <p><?php echo e(mb_substr($camp['description'] ?? '', 0, 50)); ?><?php echo mb_strlen($camp['description'] ?? '') > 50 ? '...' : ''; ?></p>
                    <div class="campaign-meta">
                        <span class="campaign-count"><?php echo e(count($camp_templates)); ?> 款精选素材</span>
                        <span class="campaign-arrow">立即查看 →</span>
                    </div>
                </div>
            </a>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<main class="main">
    <div class="grid">
        <?php foreach ($templates as $tpl): $images = format_preview_images($tpl['preview_images']); ?>
            <article class="card">
                <div style="position:relative;">
                    <img src="<?php echo e($images[0] ?? 'https://images.unsplash.com/photo-1481277542470-605612bd2d61?auto=format&fit=crop&w=1200&q=80'); ?>" alt="<?php echo e($tpl['title']); ?> 预览图">
                    <?php if ($tpl['is_editable']): ?>
                        <span style="position:absolute;top:10px;right:10px;background:linear-gradient(135deg,var(--accent),var(--accent-2));color:#fff;padding:4px 10px;border-radius:999px;font-size:0.75rem;font-weight:600;box-shadow:0 4px 12px rgba(0,0,0,0.2);">
                            ✨ 可在线编辑
                        </span>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <h3><?php echo e($tpl['title']); ?></h3>
                    <p><?php echo e($tpl['description']); ?></p>
                    <div class="tags">
                        <?php foreach (array_filter(array_map('trim', explode(',', $tpl['tags'] ?? ''))) as $tag): ?>
                            <span class="tag"><?php echo e($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-actions">
                        <?php if ($tpl['is_editable']): ?>
                            <a class="btn btn-primary" style="flex:1; text-align:center;" href="/editor.php?id=<?php echo e($tpl['id']); ?>">✨ 在线编辑</a>
                        <?php else: ?>
                            <a class="btn btn-primary" style="flex:1; text-align:center;" href="<?php echo e($tpl['download_url']); ?>" target="_blank" rel="noopener">免费下载</a>
                        <?php endif; ?>
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
