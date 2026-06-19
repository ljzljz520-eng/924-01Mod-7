<?php
require_once __DIR__ . '/../includes/campaign_repo.php';
require_once __DIR__ . '/../includes/template_repo.php';
require_once __DIR__ . '/../includes/helpers.php';

$slug = isset($_GET['slug']) ? trim($_GET['slug']) : '';
$campaign = $slug ? get_campaign_by_slug($slug) : null;

if (!$campaign) {
    header('Location: /');
    exit;
}

$is_active = is_campaign_active($campaign);
$templates = get_campaign_templates($campaign['id']);

if ($is_active) {
    record_campaign_stat($campaign['id'], null, 'view');
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo e($campaign['name']); ?> - 专题活动 | TemplateHub</title>
    <link rel="stylesheet" href="/assets/styles.css">
    <style>
        body {background: #f8fafc;}
        .campaign-wrap {max-width: 1100px;margin:0 auto;padding:0 24px 48px;}
    </style>
</head>
<body>

<div class="campaign-detail-hero" style="--theme-color: <?php echo e($campaign['theme_color']); ?>;">
    <div class="hero-content">
        <a href="/" class="campaign-back-link">← 返回首页</a>
        <h1><?php echo e($campaign['name']); ?></h1>
        <?php if ($campaign['description']): ?>
            <p><?php echo e($campaign['description']); ?></p>
        <?php endif; ?>
        <div class="campaign-time">
            📅 活动时间：<?php echo e($campaign['start_date']); ?> ~ <?php echo e($campaign['end_date']); ?>
        </div>
    </div>
</div>

<?php if (!$is_active): ?>
<div class="campaign-expired-banner">
    ⏰ 该活动已结束，感谢您的关注！您可以继续浏览下方素材，或前往首页查看最新活动。
</div>
<?php endif; ?>

<div class="campaign-wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;margin:24px 0 16px;">
        <h2 style="margin:0;font-size:1.4rem;">精选素材（<?php echo e(count($templates)); ?> 款）</h2>
    </div>

    <div class="grid">
        <?php foreach ($templates as $tpl): ?>
            <?php $images = format_preview_images($tpl['preview_images']); ?>
            <article class="card">
                <a href="/detail.php?id=<?php echo e($tpl['id']); ?>&campaign=<?php echo e($campaign['slug']); ?>" onclick="trackTemplateClick(<?php echo e($campaign['id']); ?>, <?php echo e($tpl['id']); ?>)">
                    <img src="<?php echo e($images[0] ?? 'https://images.unsplash.com/photo-1481277542470-605612bd2d61?auto=format&fit=crop&w=1200&q=80'); ?>" alt="<?php echo e($tpl['title']); ?> 预览图">
                </a>
                <div class="card-body">
                    <h3><?php echo e($tpl['title']); ?></h3>
                    <p><?php echo e($tpl['description']); ?></p>
                    <div class="tags">
                        <?php foreach (array_filter(array_map('trim', explode(',', $tpl['tags'] ?? ''))) as $tag): ?>
                            <span class="tag"><?php echo e($tag); ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="card-actions">
                        <a class="btn btn-primary" style="flex:1; text-align:center;" href="<?php echo e($tpl['download_url']); ?>" target="_blank" rel="noopener" onclick="trackTemplateDownload(<?php echo e($campaign['id']); ?>, <?php echo e($tpl['id']); ?>)">免费下载</a>
                        <a class="btn btn-ghost" style="flex:1; text-align:center;" href="/detail.php?id=<?php echo e($tpl['id']); ?>&campaign=<?php echo e($campaign['slug']); ?>" onclick="trackTemplateClick(<?php echo e($campaign['id']); ?>, <?php echo e($tpl['id']); ?>)">进入详情</a>
                    </div>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (empty($templates)): ?>
            <p style="grid-column:1/-1;text-align:center;padding:60px 0;color:#64748b;">该专题暂无素材，敬请期待~</p>
        <?php endif; ?>
    </div>

    <div style="text-align:center;margin-top:40px;">
        <a class="btn btn-ghost" href="/">← 返回首页查看更多</a>
    </div>
</div>

<footer class="footer">
    <p>TemplateHub · 免费模板库 · 后台可管理模板与下载链接。</p>
</footer>

<script>
function trackTemplateClick(campaignId, templateId) {
    if (!campaignId || !templateId) return;
    try {
        fetch('/track_campaign.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'campaign_id=' + campaignId + '&template_id=' + templateId + '&type=click'
        });
    } catch(e) {}
}

function trackTemplateDownload(campaignId, templateId) {
    if (!campaignId || !templateId) return;
    try {
        fetch('/track_campaign.php', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'campaign_id=' + campaignId + '&template_id=' + templateId + '&type=download'
        });
    } catch(e) {}
}
</script>
</body>
</html>
