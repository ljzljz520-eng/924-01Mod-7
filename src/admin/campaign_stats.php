<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/campaign_repo.php';
require_once __DIR__ . '/../includes/template_repo.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$campaign = $id ? get_campaign($id) : null;

if (!$campaign) {
    header('Location: /admin/campaigns.php');
    exit;
}

$stats = get_campaign_stats($campaign['id']);
$daily_stats = get_campaign_daily_stats($campaign['id'], 30);
$template_stats = get_campaign_template_stats($campaign['id']);
$templates = get_campaign_templates($campaign['id']);

$template_stat_map = [];
foreach ($template_stats as $ts) {
    $template_stat_map[$ts['template_id']] = $ts;
}

$conversion_rate = $stats['total_views'] > 0 ? round(($stats['total_downloads'] / $stats['total_views']) * 100, 2) : 0;
$ctr = $stats['total_views'] > 0 ? round(($stats['total_clicks'] / $stats['total_views']) * 100, 2) : 0;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>专题数据统计 - <?php echo e($campaign['name']); ?></title>
    <link rel="stylesheet" href="/assets/styles.css">
    <style>
        .wrap {max-width: 1200px;margin:40px auto;padding:0 20px;}
        .muted {color:#64748b;font-size:0.92rem;}
        .stats-cards {display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:16px;margin-bottom:24px;}
        .stat-card {background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:20px;box-shadow:0 10px 26px rgba(15,23,42,0.06);}
        .stat-card .label {color:#64748b;font-size:0.9rem;margin-bottom:8px;}
        .stat-card .value {font-size:2rem;font-weight:700;color:#0f172a;}
        .stat-card .change {font-size:0.85rem;color:#10b981;margin-top:4px;}
        table {width:100%;border-collapse:collapse;background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 10px 26px rgba(15,23,42,0.06);}
        th, td {padding:12px 14px;border-bottom:1px solid #e2e8f0;text-align:left;}
        th {background:#f8fafc;color:#475569;font-weight:600;}
        tr:last-child td {border-bottom:none;}
        .section-title {font-size:1.2rem;font-weight:600;margin:24px 0 12px;}
        .chart-container {background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:20px;box-shadow:0 10px 26px rgba(15,23,42,0.06);margin-bottom:24px;}
        .bar-chart {display:flex;align-items:flex-end;gap:6px;height:200px;padding:10px 0;border-bottom:1px solid #e2e8f0;overflow-x:auto;}
        .bar-item {flex:1;min-width:30px;display:flex;flex-direction:column;align-items:center;gap:4px;}
        .bar {width:100%;background:linear-gradient(to top, #2563eb, #60a5fa);border-radius:4px 4px 0 0;min-height:2px;transition:height 0.3s;}
        .bar-label {font-size:0.7rem;color:#64748b;}
        .bar-value {font-size:0.7rem;font-weight:600;color:#475569;}
    </style>
</head>
<body>
<div class="wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <h2 style="margin:0;"><?php echo e($campaign['name']); ?> - 数据统计</h2>
            <p class="muted">活动时间：<?php echo e($campaign['start_date']); ?> 至 <?php echo e($campaign['end_date']); ?></p>
        </div>
        <div style="display:flex;gap:10px;">
            <a class="btn btn-ghost" href="/admin/campaigns.php">返回列表</a>
            <a class="btn btn-primary" href="/admin/campaign_edit.php?id=<?php echo e($campaign['id']); ?>">编辑专题</a>
        </div>
    </div>

    <div class="stats-cards">
        <div class="stat-card">
            <div class="label">总浏览量（PV）</div>
            <div class="value"><?php echo e(number_format($stats['total_views'])); ?></div>
            <div class="change">专题页面浏览次数</div>
        </div>
        <div class="stat-card">
            <div class="label">总点击数</div>
            <div class="value"><?php echo e(number_format($stats['total_clicks'])); ?></div>
            <div class="change">素材点击进入详情次数</div>
        </div>
        <div class="stat-card">
            <div class="label">总下载数</div>
            <div class="value"><?php echo e(number_format($stats['total_downloads'])); ?></div>
            <div class="change">通过专题产生的下载量</div>
        </div>
        <div class="stat-card">
            <div class="label">点击率（CTR）</div>
            <div class="value"><?php echo e($ctr); ?>%</div>
            <div class="change">点击数 / 浏览量</div>
        </div>
        <div class="stat-card">
            <div class="label">转化率</div>
            <div class="value"><?php echo e($conversion_rate); ?>%</div>
            <div class="change">下载数 / 浏览量</div>
        </div>
    </div>

    <div class="section-title">近30天趋势</div>
    <div class="chart-container">
        <div class="bar-chart" id="trendChart">
            <?php foreach ($daily_stats as $day): ?>
                <div class="bar-item" title="<?php echo e($day['stat_date']); ?>">
                    <div class="bar-value"><?php echo e($day['views']); ?></div>
                    <div class="bar" style="height:<?php echo max(4, ($day['views'] / max(1, max(array_column($daily_stats, 'views')))) * 160); ?>px;"></div>
                    <div class="bar-label"><?php echo e(substr($day['stat_date'], 5)); ?></div>
                </div>
            <?php endforeach; ?>
            <?php if (empty($daily_stats)): ?>
                <div style="margin:auto;color:#94a3b8;">暂无数据</div>
            <?php endif; ?>
        </div>
    </div>

    <div class="section-title">素材明细</div>
    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>素材名称</th>
                <th>浏览量</th>
                <th>点击数</th>
                <th>下载数</th>
                <th>转化率</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($templates as $tpl): ?>
                <?php
                    $ts = $template_stat_map[$tpl['id']] ?? ['total_views' => 0, 'total_clicks' => 0, 'total_downloads' => 0];
                    $tpl_conv = $ts['total_views'] > 0 ? round(($ts['total_downloads'] / $ts['total_views']) * 100, 2) : 0;
                ?>
                <tr>
                    <td><?php echo e($tpl['id']); ?></td>
                    <td><?php echo e($tpl['title']); ?></td>
                    <td><?php echo e(number_format($ts['total_views'])); ?></td>
                    <td><?php echo e(number_format($ts['total_clicks'])); ?></td>
                    <td><?php echo e(number_format($ts['total_downloads'])); ?></td>
                    <td><?php echo e($tpl_conv); ?>%</td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($templates)): ?>
                <tr>
                    <td colspan="6" style="text-align:center;padding:30px;color:#94a3b8;">该专题暂无素材</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
