<?php
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/campaign_repo.php';
require_once __DIR__ . '/../includes/template_repo.php';
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/upload.php';
require_login();

$id = isset($_GET['id']) ? (int) $_GET['id'] : null;
$campaign = $id ? get_campaign($id) : null;
$error = null;
$form_data = [];

$all_templates = fetch_templates();
$selected_template_ids = $campaign ? get_campaign_template_ids($campaign['id']) : [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $cover_image = trim($_POST['existing_cover'] ?? '');
    if (!empty($_FILES['cover_image']['name']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
        $uploaded = handle_file_upload($_FILES['cover_image'], 'images');
        if ($uploaded) {
            $cover_image = $uploaded;
        }
    }

    $selected_templates = isset($_POST['template_ids']) ? array_map('intval', $_POST['template_ids']) : [];
    $sort_orders = isset($_POST['sort_orders']) ? array_map('intval', $_POST['sort_orders']) : [];

    $sorted_templates = [];
    if (!empty($selected_templates) && !empty($sort_orders)) {
        $combined = [];
        foreach ($selected_templates as $index => $tid) {
            $combined[] = [
                'id' => $tid,
                'sort' => $sort_orders[$index] ?? $index,
            ];
        }
        usort($combined, function ($a, $b) {
            return $a['sort'] - $b['sort'];
        });
        foreach ($combined as $item) {
            $sorted_templates[] = $item['id'];
        }
    }

    $payload = [
        'name' => trim($_POST['name'] ?? ''),
        'slug' => trim($_POST['slug'] ?? ''),
        'description' => trim($_POST['description'] ?? ''),
        'cover_image' => $cover_image,
        'theme_color' => trim($_POST['theme_color'] ?? '#2563eb'),
        'start_date' => trim($_POST['start_date'] ?? ''),
        'end_date' => trim($_POST['end_date'] ?? ''),
        'sort_order' => (int) ($_POST['sort_order'] ?? 0),
        'status' => (int) ($_POST['status'] ?? 1),
    ];

    if (!$payload['name'] || !$payload['start_date'] || !$payload['end_date']) {
        $error = '专题名称、开始日期和结束日期为必填项。';
        $form_data = $payload;
    } else {
        if ($campaign) {
            upsert_campaign($payload, $id);
            set_campaign_templates($id, $sorted_templates);
        } else {
            upsert_campaign($payload);
            $new_id = db()->lastInsertId();
            set_campaign_templates($new_id, $sorted_templates);
        }
        header('Location: /admin/campaigns.php');
        exit;
    }
}

$display_data = !empty($form_data) ? $form_data : ($campaign ?? []);
$display_selected = !empty($form_data) ? $selected_templates : $selected_template_ids;
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $id ? '编辑专题' : '新增专题'; ?></title>
    <link rel="stylesheet" href="/assets/styles.css">
    <style>
        .wrap {max-width: 1000px;margin:40px auto;padding:0 20px;}
        form {display:grid;gap:16px;background:#fff;border:1px solid #e2e8f0;border-radius:16px;padding:24px;box-shadow:0 14px 30px rgba(15,23,42,0.08);}
        label {font-weight:600;display:block;margin-bottom:6px;}
        input, textarea, select {width:100%;padding:12px 14px;border:1px solid #e2e8f0;border-radius:12px;font-size:1rem;}
        textarea {min-height:100px;resize:vertical;}
        .error {color:#b91c1c;background:rgba(248,113,113,0.12);border:1px solid rgba(248,113,113,0.3);padding:10px 12px;border-radius:12px;}
        .hint {color:#64748b;font-size:0.9rem;margin-top:4px;}
        .row-2 {display:grid;grid-template-columns:1fr 1fr;gap:16px;}
        .row-3 {display:grid;grid-template-columns:1fr 1fr 1fr;gap:16px;}
        .cover-preview {width:100%;max-width:300px;height:160px;object-fit:cover;border-radius:12px;border:2px dashed #e2e8f0;background:#f8fafc;}
        .templates-section {border:2px dashed #e2e8f0;padding:16px;border-radius:12px;background:#f8fafc;}
        .template-list {display:grid;gap:10px;max-height:400px;overflow-y:auto;margin-top:10px;}
        .template-item {display:flex;align-items:center;gap:12px;padding:10px;background:#fff;border:1px solid #e2e8f0;border-radius:10px;}
        .template-item.selected {border-color:#2563eb;background:rgba(37,99,235,0.03);}
        .template-item img {width:60px;height:45px;object-fit:cover;border-radius:6px;}
        .template-item .info {flex:1;min-width:0;}
        .template-item .info h4 {margin:0;font-size:0.95rem;}
        .template-item .info p {margin:2px 0 0;color:#64748b;font-size:0.82rem;}
        .template-item .sort-input {width:70px;}
        .template-item .sort-input input {padding:6px 8px;font-size:0.85rem;text-align:center;}
        .template-item input[type="checkbox"] {width:auto;}
        .selected-badge {display:inline-block;padding:2px 8px;background:#2563eb;color:#fff;border-radius:999px;font-size:0.75rem;font-weight:600;}
        .search-box {margin-bottom:10px;}
        .search-box input {padding:10px 12px;font-size:0.9rem;}
        @media (max-width:640px) {
            .row-2, .row-3 {grid-template-columns:1fr;}
        }
    </style>
</head>
<body>
<div class="wrap">
    <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px;">
        <h2 style="margin:0;"><?php echo $id ? '编辑专题' : '新增专题'; ?></h2>
        <a class="btn btn-ghost" href="/admin/campaigns.php">返回列表</a>
    </div>

    <?php if (!empty($error)): ?>
        <div class="error"><?php echo e($error); ?></div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
        <div class="row-2">
            <div>
                <label>专题名称*</label>
                <input name="name" value="<?php echo e($display_data['name'] ?? ''); ?>" placeholder="如：春节专题活动" required>
            </div>
            <div>
                <label>别名（slug）</label>
                <input name="slug" value="<?php echo e($display_data['slug'] ?? ''); ?>" placeholder="留空自动生成">
                <div class="hint">URL 友好的标识符，如 spring-festival</div>
            </div>
        </div>

        <div>
            <label>专题描述</label>
            <textarea name="description" placeholder="简要介绍本次专题活动的内容和亮点"><?php echo e($display_data['description'] ?? ''); ?></textarea>
        </div>

        <div class="row-2">
            <div>
                <label>封面图</label>
                <?php if (!empty($display_data['cover_image'])): ?>
                    <img id="coverPreview" src="<?php echo e($display_data['cover_image']); ?>" alt="封面预览" class="cover-preview">
                    <input type="hidden" name="existing_cover" value="<?php echo e($display_data['cover_image']); ?>">
                <?php else: ?>
                    <img id="coverPreview" src="" alt="封面预览" class="cover-preview" style="display:none;">
                <?php endif; ?>
                <div style="margin-top:10px;">
                    <input type="file" name="cover_image" accept="image/*" onchange="previewCover(this)">
                </div>
                <div class="hint">建议尺寸 800x400，支持 JPG/PNG/GIF</div>
            </div>
            <div>
                <div class="row-2" style="gap:12px;">
                    <div>
                        <label>主题色</label>
                        <input type="color" name="theme_color" value="<?php echo e($display_data['theme_color'] ?? '#2563eb'); ?>" style="height:46px;cursor:pointer;">
                    </div>
                    <div>
                        <label>排序</label>
                        <input type="number" name="sort_order" value="<?php echo e($display_data['sort_order'] ?? 0); ?>" min="0">
                        <div class="hint">数字越小越靠前</div>
                    </div>
                </div>
                <div style="margin-top:12px;">
                    <label>状态</label>
                    <select name="status">
                        <option value="1" <?php echo (($display_data['status'] ?? 1) == 1) ? 'selected' : ''; ?>>启用</option>
                        <option value="0" <?php echo isset($display_data['status']) && $display_data['status'] == 0 ? 'selected' : ''; ?>>下架</option>
                    </select>
                </div>
            </div>
        </div>

        <div class="row-2">
            <div>
                <label>开始日期*</label>
                <input type="date" name="start_date" value="<?php echo e($display_data['start_date'] ?? date('Y-m-d')); ?>" required>
            </div>
            <div>
                <label>结束日期*</label>
                <input type="date" name="end_date" value="<?php echo e($display_data['end_date'] ?? date('Y-m-d', strtotime('+30 days'))); ?>" required>
            </div>
        </div>

        <div class="templates-section">
            <div style="display:flex;justify-content:space-between;align-items:center;">
                <label style="margin:0;">选择素材</label>
                <span class="selected-badge" id="selectedCount">已选 0 个</span>
            </div>
            <div class="hint">勾选要加入专题的素材，并设置排序（数字越小越靠前）</div>

            <div class="search-box">
                <input type="text" id="templateSearch" placeholder="搜索素材名称..." oninput="filterTemplates()">
            </div>

            <div class="template-list" id="templateList">
                <?php foreach ($all_templates as $tpl): ?>
                    <?php
                        $images = format_preview_images($tpl['preview_images']);
                        $is_selected = in_array($tpl['id'], $display_selected);
                        $sort_index = array_search($tpl['id'], $display_selected);
                        $sort_val = $sort_index !== false ? $sort_index + 1 : 0;
                    ?>
                    <div class="template-item <?php echo $is_selected ? 'selected' : ''; ?>" data-title="<?php echo e(strtolower($tpl['title'])); ?>" data-id="<?php echo e($tpl['id']); ?>">
                        <img src="<?php echo e($images[0] ?? 'https://images.unsplash.com/photo-1481277542470-605612bd2d61?auto=format&fit=crop&w=200&q=80'); ?>" alt="">
                        <div class="info">
                            <h4><?php echo e($tpl['title']); ?></h4>
                            <p><?php echo e(mb_substr($tpl['description'] ?? '', 0, 30)); ?>...</p>
                        </div>
                        <div class="sort-input">
                            <input type="number" name="sort_orders[]" value="<?php echo e($sort_val); ?>" min="0" placeholder="排序" onchange="updateSort(this, <?php echo e($tpl['id']); ?>)">
                        </div>
                        <label style="cursor:pointer;">
                            <input type="checkbox" name="template_ids[]" value="<?php echo e($tpl['id']); ?>" <?php echo $is_selected ? 'checked' : ''; ?> onchange="toggleTemplate(this)">
                        </label>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <div style="display:flex;gap:10px;justify-content:flex-end;">
            <a class="btn btn-ghost" href="/admin/campaigns.php">取消</a>
            <button class="btn btn-primary" type="submit">保存</button>
        </div>
    </form>
</div>

<script>
function previewCover(input) {
    if (input.files && input.files[0]) {
        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('coverPreview');
            preview.src = e.target.result;
            preview.style.display = 'block';
        };
        reader.readAsDataURL(input.files[0]);
    }
}

function toggleTemplate(checkbox) {
    const item = checkbox.closest('.template-item');
    item.classList.toggle('selected', checkbox.checked);
    updateSelectedCount();
}

function updateSort(input, templateId) {
    const checkbox = document.querySelector(`.template-item[data-id="${templateId}"] input[type="checkbox"]`);
    if (checkbox && !checkbox.checked) {
        checkbox.checked = true;
        checkbox.closest('.template-item').classList.add('selected');
        updateSelectedCount();
    }
}

function updateSelectedCount() {
    const count = document.querySelectorAll('.template-item input[type="checkbox"]:checked').length;
    document.getElementById('selectedCount').textContent = '已选 ' + count + ' 个';
}

function filterTemplates() {
    const keyword = document.getElementById('templateSearch').value.toLowerCase();
    const items = document.querySelectorAll('.template-item');
    items.forEach(item => {
        const title = item.getAttribute('data-title');
        if (title.includes(keyword)) {
            item.style.display = '';
        } else {
            item.style.display = 'none';
        }
    });
}

updateSelectedCount();
</script>
</body>
</html>
