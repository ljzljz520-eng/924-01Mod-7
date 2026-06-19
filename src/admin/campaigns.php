<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/campaign_repo.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$campaigns = fetch_campaigns();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>专题管理</title>
    <link rel="stylesheet" href="/assets/styles.css">
    <style>
        .wrap {max-width: 1200px;margin:40px auto;padding:0 20px;}
        table {width:100%;border-collapse:collapse;background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 10px 26px rgba(15,23,42,0.08);}
        th, td {padding:12px 14px;border-bottom:1px solid #e2e8f0;text-align:left;}
        th {background:#f8fafc;color:#475569;font-weight:600;}
        tr:last-child td {border-bottom:none;}
        .actions {display:flex;gap:8px;flex-wrap:wrap;}
        .muted {color:#64748b;font-size:0.92rem;}
        .status-badge {display:inline-block;padding:4px 10px;border-radius:999px;font-size:0.82rem;font-weight:600;}
        .status-active {background:rgba(16,185,129,0.1);color:#059669;}
        .status-pending {background:rgba(245,158,11,0.1);color:#d97706;}
        .status-expired {background:rgba(107,114,128,0.1);color:#64748b;}
        .status-offline {background:rgba(239,68,68,0.1);color:#dc2626;}
        .cover-thumb {width:60px;height:40px;object-fit:cover;border-radius:6px;border:1px solid #e2e8f0;}
    </style>
</head>
<body>
<div class="wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <h2 style="margin:0;">专题活动管理</h2>
            <p class="muted">已登录：<?php echo e($_SESSION['admin_username'] ?? ''); ?></p>
        </div>
        <div style="display:flex;gap:10px;">
            <a class="btn btn-ghost" href="/admin/dashboard.php">模板管理</a>
            <a class="btn btn-ghost" href="/">返回前台</a>
            <a class="btn btn-primary" href="/admin/campaign_edit.php">新增专题</a>
            <a class="btn btn-ghost" href="/admin/logout.php">退出登录</a>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>封面</th>
                <th>专题名称</th>
                <th>状态</th>
                <th>开始日期</th>
                <th>结束日期</th>
                <th>排序</th>
                <th>素材数</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($campaigns as $campaign): ?>
                <?php
                    $status = get_campaign_status_label($campaign);
                    $status_class = 'status-offline';
                    if ($status === '进行中') $status_class = 'status-active';
                    elseif ($status === '待上线') $status_class = 'status-pending';
                    elseif ($status === '已过期') $status_class = 'status-expired';
                    $template_count = count(get_campaign_template_ids($campaign['id']));
                ?>
                <tr>
                    <td><?php echo e($campaign['id']); ?></td>
                    <td>
                        <?php if (!empty($campaign['cover_image'])): ?>
                            <img src="<?php echo e($campaign['cover_image']); ?>" alt="" class="cover-thumb">
                        <?php else: ?>
                            <span class="muted">无</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <div style="font-weight:600;"><?php echo e($campaign['name']); ?></div>
                        <div class="muted" style="font-size:0.82rem;">slug: <?php echo e($campaign['slug']); ?></div>
                    </td>
                    <td><span class="status-badge <?php echo $status_class; ?>"><?php echo e($status); ?></span></td>
                    <td><?php echo e($campaign['start_date']); ?></td>
                    <td><?php echo e($campaign['end_date']); ?></td>
                    <td><?php echo e($campaign['sort_order']); ?></td>
                    <td><?php echo e($template_count); ?> 个</td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-ghost" style="padding:6px 12px;font-size:0.88rem;" href="/admin/campaign_edit.php?id=<?php echo e($campaign['id']); ?>">编辑</a>
                            <a class="btn btn-ghost" style="padding:6px 12px;font-size:0.88rem;" href="/admin/campaign_stats.php?id=<?php echo e($campaign['id']); ?>">数据</a>
                            <form method="post" action="/admin/campaign_delete.php" onsubmit="return confirm('确认删除该专题？');">
                                <input type="hidden" name="id" value="<?php echo e($campaign['id']); ?>">
                                <button class="btn btn-primary" style="padding:6px 12px;font-size:0.88rem;background:#ef4444;box-shadow:none;">删除</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($campaigns)): ?>
                <tr>
                    <td colspan="9" style="text-align:center;padding:40px;color:#64748b;">暂无专题活动，点击右上角"新增专题"开始创建</td>
                </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
</body>
</html>
