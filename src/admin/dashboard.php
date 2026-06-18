<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/template_repo.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$templates = fetch_templates();
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>模板管理</title>
    <link rel="stylesheet" href="/assets/styles.css">
    <style>
        .wrap {max-width: 1100px;margin:40px auto;padding:0 20px;}
        table {width:100%;border-collapse:collapse;background:#fff;border:1px solid #e2e8f0;border-radius:12px;overflow:hidden;box-shadow:0 10px 26px rgba(15,23,42,0.08);}
        th, td {padding:12px 14px;border-bottom:1px solid #e2e8f0;text-align:left;}
        th {background:#f8fafc;color:#475569;font-weight:600;}
        tr:last-child td {border-bottom:none;}
        .actions {display:flex;gap:8px;}
        .muted {color:#64748b;font-size:0.92rem;}
    </style>
</head>
<body>
<div class="wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:16px;">
        <div>
            <h2 style="margin:0;">模板管理</h2>
            <p class="muted">已登录：<?php echo e($_SESSION['admin_username'] ?? ''); ?></p>
        </div>
        <div style="display:flex;gap:10px;">
            <a class="btn btn-ghost" href="/">返回前台</a>
            <a class="btn btn-primary" href="/admin/edit.php">新增模板</a>
            <a class="btn btn-ghost" href="/admin/logout.php">退出登录</a>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>标题</th>
                <th>标签</th>
                <th>下载链接</th>
                <th>更新时间</th>
                <th>操作</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($templates as $tpl): ?>
                <tr>
                    <td><?php echo e($tpl['id']); ?></td>
                    <td><?php echo e($tpl['title']); ?></td>
                    <td><?php echo e($tpl['tags']); ?></td>
                    <td><a href="<?php echo e($tpl['download_url']); ?>" target="_blank" rel="noopener">下载</a></td>
                    <td><?php echo e($tpl['updated_at']); ?></td>
                    <td>
                        <div class="actions">
                            <a class="btn btn-ghost" href="/admin/edit.php?id=<?php echo e($tpl['id']); ?>">编辑</a>
                            <form method="post" action="/admin/delete.php" onsubmit="return confirm('确认删除该模板？');">
                                <input type="hidden" name="id" value="<?php echo e($tpl['id']); ?>">
                                <button class="btn btn-primary" style="background:#ef4444;box-shadow:none;">删除</button>
                            </form>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
</body>
</html>
