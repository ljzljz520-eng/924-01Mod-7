<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/editor_repo.php';
require_once __DIR__ . '/../includes/helpers.php';
require_login();

$templateId = isset($_GET['template_id']) ? (int) $_GET['template_id'] : 0;
$editId = isset($_GET['edit_id']) ? (int) $_GET['edit_id'] : null;
$deleteId = isset($_GET['delete_id']) ? (int) $_GET['delete_id'] : null;

$template = $templateId ? get_template($templateId) : null;
if (!$template) {
    header('Location: /admin/dashboard.php');
    exit;
}

$regions = get_editable_regions($templateId);
$editingRegion = $editId ? get_editable_region($editId) : null;

$error = null;
$success = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    
    if ($action === 'save') {
        $regionType = $_POST['region_type'] ?? 'text';
        $regionName = trim($_POST['region_name'] ?? '');
        $isEditable = isset($_POST['is_editable']) ? 1 : 0;
        $sortOrder = (int) ($_POST['sort_order'] ?? 0);
        
        $config = [];
        
        if ($regionType === 'text') {
            $config = [
                'x' => (int) ($_POST['text_x'] ?? 0),
                'y' => (int) ($_POST['text_y'] ?? 0),
                'fontSize' => (int) ($_POST['font_size'] ?? 24),
                'fontFamily' => trim($_POST['font_family'] ?? 'Arial, sans-serif'),
                'fontWeight' => trim($_POST['font_weight'] ?? 'normal'),
                'color' => trim($_POST['text_color'] ?? '#000000'),
                'textAlign' => trim($_POST['text_align'] ?? 'left'),
                'defaultValue' => trim($_POST['text_default'] ?? ''),
                'maxLength' => (int) ($_POST['max_length'] ?? 50),
            ];
        } elseif ($regionType === 'color') {
            $config = [
                'defaultValue' => trim($_POST['color_default'] ?? '#ff6b6b'),
                'targetElements' => ['shape_overlay', 'button_bg'],
            ];
        } elseif ($regionType === 'qrcode') {
            $config = [
                'x' => (int) ($_POST['qr_x'] ?? 100),
                'y' => (int) ($_POST['qr_y'] ?? 100),
                'size' => (int) ($_POST['qr_size'] ?? 100),
                'defaultValue' => trim($_POST['qr_default'] ?? ''),
                'draggable' => isset($_POST['qr_draggable']) ? true : false,
                'minX' => (int) ($_POST['qr_min_x'] ?? 0),
                'maxX' => (int) ($_POST['qr_max_x'] ?? $template['canvas_width'] ?? 800),
                'minY' => (int) ($_POST['qr_min_y'] ?? 0),
                'maxY' => (int) ($_POST['qr_max_y'] ?? $template['canvas_height'] ?? 1200),
            ];
        }
        
        if (!$regionName) {
            $error = '区域名称不能为空';
        } else {
            $data = [
                'region_type' => $regionType,
                'region_name' => $regionName,
                'config' => $config,
                'is_editable' => $isEditable,
                'sort_order' => $sortOrder,
            ];
            
            try {
                upsert_editable_region($templateId, $data, $editId);
                $success = $editId ? '区域已更新' : '区域已添加';
                $editingRegion = null;
                $regions = get_editable_regions($templateId);
            } catch (Exception $e) {
                $error = '保存失败: ' . $e->getMessage();
            }
        }
    }
}

if ($deleteId) {
    try {
        delete_editable_region($deleteId);
        header('Location: /admin/regions.php?template_id=' . $templateId);
        exit;
    } catch (Exception $e) {
        $error = '删除失败: ' . $e->getMessage();
    }
}
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>管理可编辑区域 - <?php echo e($template['title']); ?></title>
    <link rel="stylesheet" href="/assets/styles.css">
    <style>
        .wrap {max-width: 1100px;margin:40px auto;padding:0 20px;}
        .region-card {background:#fff;border:1px solid #e2e8f0;border-radius:12px;padding:16px;margin-bottom:12px;box-shadow:0 4px 12px rgba(0,0,0,0.05);}
        .region-header {display:flex;justify-content:space-between;align-items:center;margin-bottom:10px;}
        .region-title {margin:0;font-weight:600;font-size:1.05rem;}
        .region-badge {padding:3px 10px;border-radius:999px;font-size:0.75rem;font-weight:600;}
        .badge-text {background:#dbeafe;color:#1e40af;}
        .badge-color {background:#fce7f3;color:#9d174d;}
        .badge-qrcode {background:#d1fae5;color:#065f46;}
        .badge-locked {background:#fef3c7;color:#92400e;}
        .region-config {background:#f8fafc;padding:12px;border-radius:8px;font-family:monospace;font-size:0.85rem;color:#475569;overflow-x:auto;}
        .region-actions {display:flex;gap:8px;margin-top:12px;}
        .form-grid {display:grid;grid-template-columns:repeat(auto-fit,minmax(200px,1fr));gap:12px;}
        .type-fields {display:none;}
        .type-fields.active {display:grid;}
    </style>
</head>
<body>
<div class="wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;">
        <div>
            <h2 style="margin:0 0 4px;">可编辑区域管理</h2>
            <p style="margin:0;color:#64748b;">模板：<?php echo e($template['title']); ?> (画布: <?php echo $template['canvas_width']; ?> × <?php echo $template['canvas_height']; ?>)</p>
        </div>
        <div style="display:flex;gap:10px;">
            <a class="btn btn-ghost" href="/admin/edit.php?id=<?php echo $templateId; ?>">← 返回编辑</a>
            <a class="btn btn-primary" href="/editor.php?id=<?php echo $templateId; ?>" target="_blank">🔍 预览编辑器</a>
        </div>
    </div>

    <?php if ($error): ?>
        <div class="error"><?php echo e($error); ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
        <div style="background:#d1fae5;color:#065f46;padding:12px;border-radius:12px;margin-bottom:16px;border:1px solid #10b981;"><?php echo e($success); ?></div>
    <?php endif; ?>

    <div class="region-card">
        <h3 style="margin:0 0 16px;"><?php echo $editingRegion ? '✏️ 编辑区域' : '➕ 添加新区域'; ?></h3>
        <form method="post">
            <input type="hidden" name="action" value="save">
            
            <div style="display:grid;grid-template-columns:1fr 1fr 1fr auto;gap:12px;margin-bottom:16px;">
                <div>
                    <label>区域类型</label>
                    <select name="region_type" id="regionType" onchange="showTypeFields()" style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;">
                        <option value="text" <?php echo ($editingRegion['region_type'] ?? '') === 'text' ? 'selected' : ''; ?>>文字 (text)</option>
                        <option value="color" <?php echo ($editingRegion['region_type'] ?? '') === 'color' ? 'selected' : ''; ?>>主题色 (color)</option>
                        <option value="qrcode" <?php echo ($editingRegion['region_type'] ?? '') === 'qrcode' ? 'selected' : ''; ?>>二维码 (qrcode)</option>
                    </select>
                </div>
                <div>
                    <label>区域名称</label>
                    <input name="region_name" placeholder="如：主标题" value="<?php echo e($editingRegion['region_name'] ?? ''); ?>" required>
                </div>
                <div>
                    <label>排序</label>
                    <input name="sort_order" type="number" value="<?php echo e($editingRegion['sort_order'] ?? 0); ?>">
                </div>
                <div style="display:flex;align-items:end;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="is_editable" <?php echo ($editingRegion['is_editable'] ?? 1) ? 'checked' : ''; ?>>
                        允许编辑
                    </label>
                </div>
            </div>

            <div id="textFields" class="type-fields form-grid <?php echo (!$editingRegion || $editingRegion['region_type'] === 'text') ? 'active' : ''; ?>">
                <div>
                    <label>X 坐标</label>
                    <input name="text_x" type="number" value="<?php echo e($editingRegion['config']['x'] ?? 100); ?>">
                </div>
                <div>
                    <label>Y 坐标</label>
                    <input name="text_y" type="number" value="<?php echo e($editingRegion['config']['y'] ?? 100); ?>">
                </div>
                <div>
                    <label>字号 (px)</label>
                    <input name="font_size" type="number" value="<?php echo e($editingRegion['config']['fontSize'] ?? 24); ?>">
                </div>
                <div>
                    <label>字体</label>
                    <input name="font_family" value="<?php echo e($editingRegion['config']['fontFamily'] ?? 'Arial, sans-serif'); ?>">
                </div>
                <div>
                    <label>字重</label>
                    <select name="font_weight" style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;">
                        <option value="normal" <?php echo ($editingRegion['config']['fontWeight'] ?? '') === 'normal' ? 'selected' : ''; ?>>正常</option>
                        <option value="bold" <?php echo ($editingRegion['config']['fontWeight'] ?? '') === 'bold' ? 'selected' : ''; ?>>粗体</option>
                    </select>
                </div>
                <div>
                    <label>文字颜色</label>
                    <input name="text_color" type="color" value="<?php echo e($editingRegion['config']['color'] ?? '#000000'); ?>" style="height:44px;">
                </div>
                <div>
                    <label>对齐方式</label>
                    <select name="text_align" style="width:100%;padding:10px 12px;border:1px solid #e2e8f0;border-radius:10px;">
                        <option value="left" <?php echo ($editingRegion['config']['textAlign'] ?? '') === 'left' ? 'selected' : ''; ?>>左对齐</option>
                        <option value="center" <?php echo ($editingRegion['config']['textAlign'] ?? '') === 'center' ? 'selected' : ''; ?>>居中</option>
                        <option value="right" <?php echo ($editingRegion['config']['textAlign'] ?? '') === 'right' ? 'selected' : ''; ?>>右对齐</option>
                    </select>
                </div>
                <div>
                    <label>默认值</label>
                    <input name="text_default" value="<?php echo e($editingRegion['config']['defaultValue'] ?? ''); ?>">
                </div>
                <div>
                    <label>最大长度</label>
                    <input name="max_length" type="number" value="<?php echo e($editingRegion['config']['maxLength'] ?? 50); ?>">
                </div>
            </div>

            <div id="colorFields" class="type-fields form-grid <?php echo ($editingRegion['region_type'] ?? '') === 'color' ? 'active' : ''; ?>">
                <div>
                    <label>默认颜色</label>
                    <input name="color_default" type="color" value="<?php echo e($editingRegion['config']['defaultValue'] ?? '#ff6b6b'); ?>" style="height:44px;">
                </div>
            </div>

            <div id="qrcodeFields" class="type-fields form-grid <?php echo ($editingRegion['region_type'] ?? '') === 'qrcode' ? 'active' : ''; ?>">
                <div>
                    <label>X 坐标（中心）</label>
                    <input name="qr_x" type="number" value="<?php echo e($editingRegion['config']['x'] ?? 100); ?>">
                </div>
                <div>
                    <label>Y 坐标（中心）</label>
                    <input name="qr_y" type="number" value="<?php echo e($editingRegion['config']['y'] ?? 100); ?>">
                </div>
                <div>
                    <label>尺寸 (px)</label>
                    <input name="qr_size" type="number" value="<?php echo e($editingRegion['config']['size'] ?? 100); ?>">
                </div>
                <div>
                    <label>默认链接</label>
                    <input name="qr_default" value="<?php echo e($editingRegion['config']['defaultValue'] ?? ''); ?>">
                </div>
                <div style="display:flex;align-items:end;">
                    <label style="display:flex;align-items:center;gap:8px;cursor:pointer;">
                        <input type="checkbox" name="qr_draggable" <?php echo ($editingRegion['config']['draggable'] ?? true) ? 'checked' : ''; ?>>
                        允许拖拽
                    </label>
                </div>
                <div>
                    <label>最小 X</label>
                    <input name="qr_min_x" type="number" value="<?php echo e($editingRegion['config']['minX'] ?? 0); ?>">
                </div>
                <div>
                    <label>最大 X</label>
                    <input name="qr_max_x" type="number" value="<?php echo e($editingRegion['config']['maxX'] ?? $template['canvas_width'] ?? 800); ?>">
                </div>
                <div>
                    <label>最小 Y</label>
                    <input name="qr_min_y" type="number" value="<?php echo e($editingRegion['config']['minY'] ?? 0); ?>">
                </div>
                <div>
                    <label>最大 Y</label>
                    <input name="qr_max_y" type="number" value="<?php echo e($editingRegion['config']['maxY'] ?? $template['canvas_height'] ?? 1200); ?>">
                </div>
            </div>

            <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:16px;">
                <?php if ($editingRegion): ?>
                    <a class="btn btn-ghost" href="/admin/regions.php?template_id=<?php echo $templateId; ?>">取消编辑</a>
                <?php endif; ?>
                <button class="btn btn-primary" type="submit"><?php echo $editingRegion ? '更新区域' : '添加区域'; ?></button>
            </div>
        </form>
    </div>

    <h3 style="margin:24px 0 12px;">已有区域 (<?php echo count($regions); ?>)</h3>
    <?php if (empty($regions)): ?>
        <div class="region-card">
            <p style="margin:0;color:#64748b;text-align:center;padding:20px;">暂无可编辑区域，请添加</p>
        </div>
    <?php else: ?>
        <?php foreach ($regions as $region): ?>
            <div class="region-card">
                <div class="region-header">
                    <div style="display:flex;align-items:center;gap:10px;">
                        <h4 class="region-title"><?php echo e($region['region_name']); ?></h4>
                        <span class="region-badge badge-<?php echo $region['region_type']; ?>">
                            <?php echo $region['region_type']; ?>
                        </span>
                        <?php if (!$region['is_editable']): ?>
                            <span class="region-badge badge-locked">🔒 锁定</span>
                        <?php endif; ?>
                    </div>
                    <span style="color:#94a3b8;font-size:0.85rem;">排序: <?php echo $region['sort_order']; ?></span>
                </div>
                <div class="region-config">
                    <?php echo json_encode($region['config'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE); ?>
                </div>
                <div class="region-actions">
                    <a class="btn btn-ghost" href="/admin/regions.php?template_id=<?php echo $templateId; ?>&edit_id=<?php echo $region['id']; ?>">编辑</a>
                    <a class="btn btn-ghost" href="/admin/regions.php?template_id=<?php echo $templateId; ?>&delete_id=<?php echo $region['id']; ?>" onclick="return confirm('确定删除此区域？')" style="color:#dc2626;">删除</a>
                </div>
            </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function showTypeFields() {
    const type = document.getElementById('regionType').value;
    document.querySelectorAll('.type-fields').forEach(el => el.classList.remove('active'));
    document.getElementById(type + 'Fields').classList.add('active');
}
</script>
</body>
</html>
