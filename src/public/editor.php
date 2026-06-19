<?php
require_once __DIR__ . '/../includes/editor_repo.php';
require_once __DIR__ . '/../includes/helpers.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$template = $id ? get_template_with_regions($id) : null;

if (!$template || empty($template['source_image'])) {
    header('Location: /');
    exit;
}

$hasEditableRegions = !empty(array_filter($template['editable_regions'], fn($r) => $r['is_editable']));
$editableJson = json_encode($template['editable_regions'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
$templateJson = json_encode($template, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
?>
<!DOCTYPE html>
<html lang="zh-CN">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>在线编辑 - <?php echo e($template['title']); ?> | TemplateHub</title>
    <link rel="stylesheet" href="/assets/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.5.3/build/qrcode.min.js"></script>
    <style>
        .editor-wrap { max-width: 1400px; margin: 24px auto; padding: 0 20px; }
        .editor-container { display: grid; grid-template-columns: 320px 1fr; gap: 20px; }
        .editor-sidebar { background: #fff; border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow); align-self: start; }
        .editor-preview { background: #fff; border: 1px solid var(--border); border-radius: var(--radius); padding: 20px; box-shadow: var(--shadow); }
        .control-group { margin-bottom: 20px; padding-bottom: 20px; border-bottom: 1px solid var(--border); }
        .control-group:last-child { border-bottom: none; margin-bottom: 0; padding-bottom: 0; }
        .control-group h3 { margin: 0 0 12px; font-size: 1rem; color: var(--text); }
        .control-group label { display: block; font-weight: 600; margin-bottom: 6px; font-size: 0.9rem; }
        .control-group input[type="text"], .control-group input[type="url"] { width: 100%; padding: 10px 12px; border: 1px solid var(--border); border-radius: 10px; font-size: 0.95rem; }
        .control-group input[type="color"] { width: 100%; height: 44px; padding: 2px; border: 1px solid var(--border); border-radius: 10px; cursor: pointer; }
        .color-presets { display: flex; gap: 8px; margin-top: 10px; flex-wrap: wrap; }
        .color-preset { width: 32px; height: 32px; border-radius: 50%; cursor: pointer; border: 2px solid #fff; box-shadow: 0 2px 6px rgba(0,0,0,0.15); transition: transform 0.15s; }
        .color-preset:hover { transform: scale(1.1); }
        .preview-canvas-container { display: flex; justify-content: center; align-items: center; min-height: 500px; background: #f1f5f9; border-radius: 12px; padding: 20px; }
        #previewCanvas { max-width: 100%; height: auto; border-radius: 8px; box-shadow: 0 10px 30px rgba(0,0,0,0.15); cursor: move; }
        #previewCanvas.dragging { cursor: grabbing; }
        .editor-actions { display: flex; gap: 10px; margin-top: 20px; }
        .editor-actions .btn { flex: 1; text-align: center; }
        .generated-preview { margin-top: 20px; padding: 16px; background: #f0f9ff; border: 1px solid #93c5fd; border-radius: 12px; }
        .generated-preview h4 { margin: 0 0 10px; color: #1e40af; }
        .generated-preview img { max-width: 100%; border-radius: 8px; border: 1px solid var(--border); }
        .download-section { margin-top: 20px; padding: 20px; background: linear-gradient(135deg, #ecfdf5, #f0fdf4); border: 2px solid #10b981; border-radius: 12px; }
        .download-section h4 { margin: 0 0 10px; color: #065f46; }
        .locked-note { margin-top: 10px; padding: 10px; background: #fef3c7; border: 1px solid #f59e0b; border-radius: 8px; font-size: 0.85rem; color: #92400e; }
        .qrcode-position-hint { font-size: 0.8rem; color: var(--muted); margin-top: 6px; }
        .back-link { display: inline-block; margin-bottom: 16px; color: var(--accent); font-weight: 600; }
        @media (max-width: 900px) {
            .editor-container { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>
<div class="editor-wrap">
    <a href="/detail.php?id=<?php echo $template['id']; ?>" class="back-link">← 返回详情页</a>
    
    <h1 style="margin: 0 0 20px; font-size: 1.6rem;">在线编辑：<?php echo e($template['title']); ?></h1>
    
    <?php if (!$hasEditableRegions): ?>
        <div class="notice">此模板暂未开放任何可编辑区域，请联系作者。</div>
    <?php endif; ?>

    <div class="editor-container">
        <div class="editor-sidebar">
            <div id="controlsContainer">
            </div>
            
            <div class="editor-actions">
                <button class="btn btn-primary" onclick="generatePreview()">生成预览</button>
                <button class="btn btn-ghost" onclick="resetEdits()">重置</button>
            </div>
        </div>
        
        <div class="editor-preview">
            <h3 style="margin: 0 0 12px;">实时预览</h3>
            <div class="preview-canvas-container">
                <canvas id="previewCanvas"></canvas>
            </div>
            
            <div id="generatedPreview" style="display:none;" class="generated-preview">
                <h4>🎨 服务器生成预览（带水印）</h4>
                <img id="serverPreviewImg" alt="服务器生成预览">
                <div style="margin-top: 12px; display: flex; gap: 10px;">
                    <button class="btn btn-primary" onclick="simulatePurchase()">🔒 购买并下载高清原图</button>
                    <a id="downloadBtn" class="btn btn-ghost" style="display:none; flex:1; text-align:center;" href="#" download="custom-poster.jpg">⬇️ 下载高清无水印</a>
                </div>
                <p class="locked-note" id="purchaseNote">预览图带有水印，仅用于效果确认。购买后可下载高清无水印原图。</p>
            </div>
        </div>
    </div>
</div>

<footer class="footer">
    <p>TemplateHub · 在线编辑功能 · 所有编辑均不破坏原模板图层</p>
</footer>

<script>
const templateData = <?php echo $templateJson; ?>;
const editableRegions = <?php echo $editableJson; ?>;

let currentConfig = {};
let qrCodeDataUrls = {};
let isDragging = false;
let dragTarget = null;
let dragOffset = { x: 0, y: 0 };
let scale = 1;
let canvasOffset = { x: 0, y: 0 };
let currentOrder = null;

const canvas = document.getElementById('previewCanvas');
const ctx = canvas.getContext('2d');
const baseImage = new Image();
baseImage.crossOrigin = 'anonymous';

function init() {
    canvas.width = templateData.canvas_width || 800;
    canvas.height = templateData.canvas_height || 1200;
    
    buildControls();
    initDefaultValues();
    
    baseImage.onload = () => {
        renderCanvas();
    };
    baseImage.src = templateData.source_image;
    
    canvas.addEventListener('mousedown', startDrag);
    canvas.addEventListener('mousemove', onDrag);
    canvas.addEventListener('mouseup', endDrag);
    canvas.addEventListener('mouseleave', endDrag);
    
    canvas.addEventListener('touchstart', handleTouchStart, { passive: false });
    canvas.addEventListener('touchmove', handleTouchMove, { passive: false });
    canvas.addEventListener('touchend', endDrag);
    
    window.addEventListener('resize', updateScale);
}

function updateScale() {
    const rect = canvas.getBoundingClientRect();
    scale = canvas.width / rect.width;
    canvasOffset = { x: rect.left, y: rect.top };
}

function buildControls() {
    const container = document.getElementById('controlsContainer');
    container.innerHTML = '';
    
    const textRegions = editableRegions.filter(r => r.region_type === 'text' && r.is_editable);
    const colorRegions = editableRegions.filter(r => r.region_type === 'color' && r.is_editable);
    const qrRegions = editableRegions.filter(r => r.region_type === 'qrcode' && r.is_editable);
    
    if (textRegions.length > 0) {
        const group = createControlGroup('✏️ 文字编辑');
        textRegions.forEach(region => {
            const config = region.config;
            const key = 'text_' + region.id;
            
            const label = document.createElement('label');
            label.textContent = region.region_name;
            group.appendChild(label);
            
            const input = document.createElement('input');
            input.type = 'text';
            input.value = config.defaultValue || '';
            input.maxLength = config.maxLength || 50;
            input.placeholder = `输入${region.region_name}...`;
            input.oninput = () => {
                currentConfig[key] = input.value;
                renderCanvas();
            };
            group.appendChild(input);
            
            if (config.is_editable === 0) {
                input.disabled = true;
                const hint = document.createElement('div');
                hint.className = 'qrcode-position-hint';
                hint.textContent = '🔒 作者已锁定此区域';
                group.appendChild(hint);
            }
            
            currentConfig[key] = config.defaultValue || '';
        });
        container.appendChild(group);
    }
    
    if (colorRegions.length > 0) {
        const group = createControlGroup('🎨 主题色彩');
        colorRegions.forEach(region => {
            const config = region.config;
            const key = 'color_' + region.id;
            
            const label = document.createElement('label');
            label.textContent = region.region_name;
            group.appendChild(label);
            
            const colorInput = document.createElement('input');
            colorInput.type = 'color';
            colorInput.value = config.defaultValue || '#ff6b6b';
            colorInput.oninput = () => {
                currentConfig[key] = colorInput.value;
                renderCanvas();
            };
            group.appendChild(colorInput);
            
            const presets = document.createElement('div');
            presets.className = 'color-presets';
            const colors = ['#ff6b6b', '#f59e0b', '#10b981', '#3b82f6', '#8b5cf6', '#ec4899', '#1f2937', '#ffffff'];
            colors.forEach(c => {
                const preset = document.createElement('div');
                preset.className = 'color-preset';
                preset.style.background = c;
                preset.title = c;
                preset.onclick = () => {
                    colorInput.value = c;
                    currentConfig[key] = c;
                    renderCanvas();
                };
                presets.appendChild(preset);
            });
            group.appendChild(presets);
            
            currentConfig[key] = config.defaultValue || '#ff6b6b';
        });
        container.appendChild(group);
    }
    
    if (qrRegions.length > 0) {
        const group = createControlGroup('📱 二维码设置');
        qrRegions.forEach(region => {
            const config = region.config;
            const key = 'qrcode_' + region.id;
            const dataKey = 'qrcode_data_' + region.id;
            
            const label = document.createElement('label');
            label.textContent = region.region_name + ' 内容';
            group.appendChild(label);
            
            const input = document.createElement('input');
            input.type = 'url';
            input.value = config.defaultValue || '';
            input.placeholder = 'https://example.com';
            input.oninput = () => {
                currentConfig[dataKey] = input.value;
                currentConfig[key] = input.value;
                updateQrCode(region.id, input.value);
            };
            group.appendChild(input);
            
            if (config.draggable) {
                const hint = document.createElement('div');
                hint.className = 'qrcode-position-hint';
                hint.textContent = '💡 提示：可在预览图上拖拽调整二维码位置';
                group.appendChild(hint);
            }
            
            currentConfig[key] = config.defaultValue || '';
            currentConfig[dataKey] = config.defaultValue || '';
            currentConfig['qrcode_x_' + region.id] = config.x;
            currentConfig['qrcode_y_' + region.id] = config.y;
            
            updateQrCode(region.id, config.defaultValue || '');
        });
        container.appendChild(group);
    }
}

function createControlGroup(title) {
    const group = document.createElement('div');
    group.className = 'control-group';
    const h3 = document.createElement('h3');
    h3.textContent = title;
    group.appendChild(h3);
    return group;
}

function initDefaultValues() {
    editableRegions.forEach(region => {
        if (!region.is_editable) {
            const key = region.region_type + '_' + region.id;
            const config = region.config;
            if (region.region_type === 'text') {
                currentConfig[key] = config.defaultValue || '';
            } else if (region.region_type === 'qrcode') {
                currentConfig[key] = config.defaultValue || '';
                currentConfig['qrcode_data_' + region.id] = config.defaultValue || '';
                currentConfig['qrcode_x_' + region.id] = config.x;
                currentConfig['qrcode_y_' + region.id] = config.y;
                updateQrCode(region.id, config.defaultValue || '');
            }
        }
    });
}

async function updateQrCode(regionId, data) {
    if (!data) {
        delete qrCodeDataUrls[regionId];
        delete currentConfig['qrcode_image_' + regionId];
        renderCanvas();
        return;
    }
    
    try {
        const dataUrl = await QRCode.toDataURL(data, {
            width: 256,
            margin: 1,
            color: { dark: '#000000', light: '#ffffff' }
        });
        qrCodeDataUrls[regionId] = dataUrl;
        currentConfig['qrcode_image_' + regionId] = dataUrl;
        renderCanvas();
    } catch (e) {
        console.error('QR code generation failed:', e);
    }
}

function renderCanvas() {
    ctx.clearRect(0, 0, canvas.width, canvas.height);
    
    if (baseImage.complete && baseImage.naturalWidth > 0) {
        ctx.drawImage(baseImage, 0, 0, canvas.width, canvas.height);
    }
    
    let themeColor = null;
    editableRegions.forEach(region => {
        if (region.region_type === 'color') {
            const key = 'color_' + region.id;
            themeColor = currentConfig[key] || region.config.defaultValue;
        }
    });
    
    if (themeColor) {
        applyColorOverlay(themeColor, 0.25);
    }
    
    editableRegions.forEach(region => {
        const config = region.config;
        const key = region.region_type + '_' + region.id;
        const value = currentConfig[key] ?? config.defaultValue;
        
        if (region.region_type === 'text' && value) {
            drawText(config, value);
        } else if (region.region_type === 'qrcode') {
            const x = currentConfig['qrcode_x_' + region.id] ?? config.x;
            const y = currentConfig['qrcode_y_' + region.id] ?? config.y;
            const size = config.size || 100;
            
            if (qrCodeDataUrls[region.id]) {
                const img = new Image();
                img.onload = () => {
                    ctx.drawImage(img, x - size/2, y - size/2, size, size);
                };
                img.src = qrCodeDataUrls[region.id];
            } else {
                drawQrPlaceholder(x, y, size);
            }
            
            if (config.draggable) {
                ctx.strokeStyle = 'rgba(59, 130, 246, 0.8)';
                ctx.lineWidth = 2;
                ctx.setLineDash([5, 5]);
                ctx.strokeRect(x - size/2, y - size/2, size, size);
                ctx.setLineDash([]);
            }
        }
    });
    
    updateScale();
}

function applyColorOverlay(color, opacity) {
    const rgb = hexToRgb(color);
    const imgData = ctx.getImageData(0, 0, canvas.width, canvas.height);
    const data = imgData.data;
    
    for (let i = 0; i < data.length; i += 4) {
        data[i] = data[i] * (1 - opacity) + rgb.r * opacity;
        data[i + 1] = data[i + 1] * (1 - opacity) + rgb.g * opacity;
        data[i + 2] = data[i + 2] * (1 - opacity) + rgb.b * opacity;
    }
    
    ctx.putImageData(imgData, 0, 0);
}

function drawText(config, text) {
    const x = config.x || 0;
    const y = config.y || 0;
    const fontSize = config.fontSize || 24;
    const color = config.color || '#000000';
    const textAlign = config.textAlign || 'left';
    const fontWeight = config.fontWeight || 'normal';
    
    ctx.font = `${fontWeight} ${fontSize}px ${config.fontFamily || 'Arial, sans-serif'}`;
    ctx.fillStyle = color;
    ctx.textAlign = textAlign;
    ctx.textBaseline = 'top';
    
    ctx.fillText(text, x, y);
}

function drawQrPlaceholder(x, y, size) {
    ctx.fillStyle = '#ffffff';
    ctx.fillRect(x - size/2, y - size/2, size, size);
    ctx.strokeStyle = '#000000';
    ctx.lineWidth = 1;
    ctx.strokeRect(x - size/2, y - size/2, size, size);
    
    ctx.fillStyle = '#000000';
    const cellSize = size / 21;
    for (let row = 0; row < 21; row++) {
        for (let col = 0; col < 21; col++) {
            if ((row < 7 && col < 7) || (row < 7 && col > 13) || (row > 13 && col < 7)) {
                if (row === 0 || row === 6 || col === 0 || col === 6 || col === 14 || col === 20 || row === 14 || row === 20) {
                    ctx.fillRect(x - size/2 + col * cellSize, y - size/2 + row * cellSize, cellSize, cellSize);
                } else if (!(row === 1 || row === 5 || col === 1 || col === 5 || col === 15 || col === 19 || row === 15 || row === 19)) {
                    if (!(row >= 2 && row <= 4 && col >= 2 && col <= 4) &&
                        !(row >= 2 && row <= 4 && col >= 16 && col <= 18) &&
                        !(row >= 16 && row <= 18 && col >= 2 && col <= 4)) {
                        ctx.fillRect(x - size/2 + col * cellSize, y - size/2 + row * cellSize, cellSize, cellSize);
                    }
                }
            } else if ((row + col) % 3 === 0) {
                ctx.fillRect(x - size/2 + col * cellSize, y - size/2 + row * cellSize, cellSize * 0.8, cellSize * 0.8);
            }
        }
    }
}

function hexToRgb(hex) {
    const result = /^#?([a-f\d]{2})([a-f\d]{2})([a-f\d]{2})$/i.exec(hex);
    return result ? {
        r: parseInt(result[1], 16),
        g: parseInt(result[2], 16),
        b: parseInt(result[3], 16)
    } : { r: 0, g: 0, b: 0 };
}

function getCanvasCoords(clientX, clientY) {
    const rect = canvas.getBoundingClientRect();
    return {
        x: (clientX - rect.left) * (canvas.width / rect.width),
        y: (clientY - rect.top) * (canvas.height / rect.height)
    };
}

function findQrAtPosition(x, y) {
    for (const region of editableRegions) {
        if (region.region_type === 'qrcode' && region.config.draggable) {
            const config = region.config;
            const qx = currentConfig['qrcode_x_' + region.id] ?? config.x;
            const qy = currentConfig['qrcode_y_' + region.id] ?? config.y;
            const size = config.size || 100;
            
            if (x >= qx - size/2 && x <= qx + size/2 &&
                y >= qy - size/2 && y <= qy + size/2) {
                return { region, offsetX: x - qx, offsetY: y - qy };
            }
        }
    }
    return null;
}

function startDrag(e) {
    const coords = getCanvasCoords(e.clientX, e.clientY);
    const found = findQrAtPosition(coords.x, coords.y);
    
    if (found) {
        isDragging = true;
        dragTarget = found.region;
        dragOffset = { x: found.offsetX, y: found.offsetY };
        canvas.classList.add('dragging');
    }
}

function handleTouchStart(e) {
    e.preventDefault();
    const touch = e.touches[0];
    const coords = getCanvasCoords(touch.clientX, touch.clientY);
    const found = findQrAtPosition(coords.x, coords.y);
    
    if (found) {
        isDragging = true;
        dragTarget = found.region;
        dragOffset = { x: found.offsetX, y: found.offsetY };
        canvas.classList.add('dragging');
    }
}

function onDrag(e) {
    if (!isDragging || !dragTarget) return;
    
    const coords = getCanvasCoords(e.clientX, e.clientY);
    const config = dragTarget.config;
    
    let newX = coords.x - dragOffset.x;
    let newY = coords.y - dragOffset.y;
    
    if (config.minX !== undefined) newX = Math.max(config.minX, newX);
    if (config.maxX !== undefined) newX = Math.min(config.maxX, newX);
    if (config.minY !== undefined) newY = Math.max(config.minY, newY);
    if (config.maxY !== undefined) newY = Math.min(config.maxY, newY);
    
    currentConfig['qrcode_x_' + dragTarget.id] = newX;
    currentConfig['qrcode_y_' + dragTarget.id] = newY;
    
    renderCanvas();
}

function handleTouchMove(e) {
    if (!isDragging || !dragTarget) return;
    e.preventDefault();
    
    const touch = e.touches[0];
    const coords = getCanvasCoords(touch.clientX, touch.clientY);
    const config = dragTarget.config;
    
    let newX = coords.x - dragOffset.x;
    let newY = coords.y - dragOffset.y;
    
    if (config.minX !== undefined) newX = Math.max(config.minX, newX);
    if (config.maxX !== undefined) newX = Math.min(config.maxX, newX);
    if (config.minY !== undefined) newY = Math.max(config.minY, newY);
    if (config.maxY !== undefined) newY = Math.min(config.maxY, newY);
    
    currentConfig['qrcode_x_' + dragTarget.id] = newX;
    currentConfig['qrcode_y_' + dragTarget.id] = newY;
    
    renderCanvas();
}

function endDrag() {
    isDragging = false;
    dragTarget = null;
    canvas.classList.remove('dragging');
}

async function generatePreview() {
    try {
        const response = await fetch('/api/preview.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                template_id: templateData.id,
                config: currentConfig
            })
        });
        
        const data = await response.json();
        if (data.success) {
            currentOrder = data;
            document.getElementById('serverPreviewImg').src = data.preview_url + '&t=' + Date.now();
            document.getElementById('generatedPreview').style.display = 'block';
            document.getElementById('generatedPreview').scrollIntoView({ behavior: 'smooth' });
        } else {
            alert('生成预览失败: ' + (data.error || '未知错误'));
        }
    } catch (e) {
        alert('网络错误，请稍后重试');
        console.error(e);
    }
}

async function simulatePurchase() {
    if (!currentOrder) {
        alert('请先生成预览');
        return;
    }
    
    if (!confirm('模拟支付 ¥9.9 元购买此模板的高清下载权限？')) {
        return;
    }
    
    try {
        const response = await fetch('/api/simulate_payment.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ order_id: currentOrder.order_id })
        });
        
        const data = await response.json();
        if (data.success) {
            alert('✅ ' + data.message);
            const downloadBtn = document.getElementById('downloadBtn');
            downloadBtn.href = '/api/download.php?token=' + currentOrder.download_token;
            downloadBtn.style.display = 'block';
            document.getElementById('purchaseNote').innerHTML = '✅ 已支付！点击上方按钮下载高清无水印原图（分辨率：' + templateData.canvas_width + ' × ' + templateData.canvas_height + '）';
            document.getElementById('purchaseNote').style.background = '#d1fae5';
            document.getElementById('purchaseNote').style.borderColor = '#10b981';
            document.getElementById('purchaseNote').style.color = '#065f46';
        } else {
            alert('支付失败: ' + (data.error || '未知错误'));
        }
    } catch (e) {
        alert('网络错误，请稍后重试');
        console.error(e);
    }
}

function resetEdits() {
    if (!confirm('确定要重置所有编辑内容吗？')) return;
    currentConfig = {};
    qrCodeDataUrls = {};
    buildControls();
    initDefaultValues();
    renderCanvas();
    document.getElementById('generatedPreview').style.display = 'none';
    currentOrder = null;
}

init();
</script>
</body>
</html>
