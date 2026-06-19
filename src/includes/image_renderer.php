<?php

function hex_to_rgb(string $hex): array
{
    $hex = ltrim($hex, '#');
    if (strlen($hex) === 3) {
        $hex = $hex[0] . $hex[0] . $hex[1] . $hex[1] . $hex[2] . $hex[2];
    }
    return [
        'r' => hexdec(substr($hex, 0, 2)),
        'g' => hexdec(substr($hex, 2, 2)),
        'b' => hexdec(substr($hex, 4, 2)),
    ];
}

function load_image(string $url): GdImage|false
{
    if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) {
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_FOLLOWLOCATION, true);
        curl_setopt($ch, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        $imageData = curl_exec($ch);
        curl_close($ch);
        if ($imageData === false) {
            return false;
        }
        return imagecreatefromstring($imageData);
    }
    
    $localPath = __DIR__ . '/../public' . $url;
    if (file_exists($localPath)) {
        $ext = strtolower(pathinfo($localPath, PATHINFO_EXTENSION));
        return match ($ext) {
            'jpg', 'jpeg' => imagecreatefromjpeg($localPath),
            'png' => imagecreatefrompng($localPath),
            'gif' => imagecreatefromgif($localPath),
            'webp' => imagecreatefromwebp($localPath),
            default => imagecreatefromstring(file_get_contents($localPath)),
        };
    }
    
    return false;
}

function apply_color_overlay(GdImage $image, string $color, float $opacity = 0.3): void
{
    $width = imagesx($image);
    $height = imagesy($image);
    $rgb = hex_to_rgb($color);
    $alpha = (int) ((1 - $opacity) * 127);
    
    for ($y = 0; $y < $height; $y++) {
        for ($x = 0; $x < $width; $x++) {
            $currentColor = imagecolorat($image, $x, $y);
            $currentR = ($currentColor >> 16) & 0xFF;
            $currentG = ($currentColor >> 8) & 0xFF;
            $currentB = $currentColor & 0xFF;
            
            $newR = (int) ($currentR * (1 - $opacity) + $rgb['r'] * $opacity);
            $newG = (int) ($currentG * (1 - $opacity) + $rgb['g'] * $opacity);
            $newB = (int) ($currentB * (1 - $opacity) + $rgb['b'] * $opacity);
            
            $newColor = imagecolorallocatealpha($image, $newR, $newG, $newB, $alpha);
            imagesetpixel($image, $x, $y, $newColor);
        }
    }
}

function render_text(GdImage $image, array $textConfig, string $text): void
{
    $x = (int) ($textConfig['x'] ?? 0);
    $y = (int) ($textConfig['y'] ?? 0);
    $fontSize = (int) ($textConfig['fontSize'] ?? 24);
    $color = $textConfig['color'] ?? '#000000';
    $textAlign = $textConfig['textAlign'] ?? 'left';
    $fontWeight = $textConfig['fontWeight'] ?? 'normal';
    
    $rgb = hex_to_rgb($color);
    $textColor = imagecolorallocate($image, $rgb['r'], $rgb['g'], $rgb['b']);
    
    $gdFontSize = (int) ($fontSize / 3);
    if ($gdFontSize < 1) $gdFontSize = 1;
    if ($gdFontSize > 5) $gdFontSize = 5;
    
    $textWidth = imagefontwidth($gdFontSize) * strlen($text);
    
    if ($textAlign === 'center') {
        $x -= (int) ($textWidth / 2);
    } elseif ($textAlign === 'right') {
        $x -= $textWidth;
    }
    
    if ($fontWeight === 'bold') {
        for ($i = 0; $i < 2; $i++) {
            imagestring($image, $gdFontSize, $x + $i, $y, $text, $textColor);
        }
    } else {
        imagestring($image, $gdFontSize, $x, $y, $text, $textColor);
    }
}

function render_qrcode_placeholder(GdImage $image, array $qrConfig, string $data): void
{
    $x = (int) ($qrConfig['x'] ?? 0);
    $y = (int) ($qrConfig['y'] ?? 0);
    $size = (int) ($qrConfig['size'] ?? 100);
    
    $halfSize = (int) ($size / 2);
    $qrX = $x - $halfSize;
    $qrY = $y - $halfSize;
    
    $white = imagecolorallocate($image, 255, 255, 255);
    imagefilledrectangle($image, $qrX, $qrY, $qrX + $size, $qrY + $size, $white);
    
    $black = imagecolorallocate($image, 0, 0, 0);
    $cellSize = (int) ($size / 21);
    if ($cellSize < 2) $cellSize = 2;
    
    $pattern = [
        [1,1,1,1,1,1,1,0,1,0,1,0,1,1,1,1,1,1,1],
        [1,0,0,0,0,0,1,0,0,1,0,1,1,0,0,0,0,0,1],
        [1,0,1,1,1,0,1,0,1,0,1,0,1,0,1,1,1,0,1],
        [1,0,1,1,1,0,1,0,0,1,0,1,1,0,1,1,1,0,1],
        [1,0,1,1,1,0,1,0,1,1,1,0,1,0,1,1,1,0,1],
        [1,0,0,0,0,0,1,0,0,0,0,1,1,0,0,0,0,0,1],
        [1,1,1,1,1,1,1,0,1,0,1,0,1,1,1,1,1,1,1],
        [0,0,0,0,0,0,0,0,0,1,0,1,0,0,0,0,0,0,0],
        [1,0,1,0,1,0,1,0,1,0,1,0,1,0,1,0,1,0,1],
        [0,1,0,1,0,1,0,0,0,1,0,1,0,1,0,1,0,1,0],
        [1,0,1,0,1,0,1,0,1,1,1,0,1,0,1,0,1,0,1],
        [0,0,0,0,0,0,0,0,0,0,0,1,0,0,0,0,0,0,0],
        [1,1,1,1,1,1,1,0,1,0,1,0,1,0,1,0,1,0,1],
        [1,0,0,0,0,0,1,0,0,1,0,1,0,1,0,1,0,1,0],
        [1,0,1,1,1,0,1,0,1,0,1,0,1,0,1,0,1,0,1],
        [1,0,1,1,1,0,1,0,0,1,0,1,0,1,0,1,0,1,0],
        [1,0,1,1,1,0,1,0,1,0,1,0,1,0,1,0,1,0,1],
        [1,0,0,0,0,0,1,0,0,1,0,1,0,1,0,1,0,1,0],
        [1,1,1,1,1,1,1,0,1,0,1,0,1,0,1,0,1,0,1],
    ];
    
    $offsetX = (int) (($size - count($pattern[0]) * $cellSize) / 2);
    $offsetY = (int) (($size - count($pattern) * $cellSize) / 2);
    
    foreach ($pattern as $row => $cols) {
        foreach ($cols as $col => $filled) {
            if ($filled) {
                imagefilledrectangle(
                    $image,
                    $qrX + $offsetX + $col * $cellSize,
                    $qrY + $offsetY + $row * $cellSize,
                    $qrX + $offsetX + ($col + 1) * $cellSize - 1,
                    $qrY + $offsetY + ($row + 1) * $cellSize - 1,
                    $black
                );
            }
        }
    }
    
    $cornerSize = 7 * $cellSize;
    imagefilledrectangle($image, $qrX + $offsetX, $qrY + $offsetY, $qrX + $offsetX + $cornerSize, $qrY + $offsetY + 2, $black);
    imagefilledrectangle($image, $qrX + $offsetX, $qrY + $offsetY, $qrX + $offsetX + 2, $qrY + $offsetY + $cornerSize, $black);
    imagefilledrectangle($image, $qrX + $offsetX, $qrY + $offsetY + $cornerSize - 2, $qrX + $offsetX + $cornerSize, $qrY + $offsetY + $cornerSize, $black);
    imagefilledrectangle($image, $qrX + $offsetX + $cornerSize - 2, $qrY + $offsetY, $qrX + $offsetX + $cornerSize, $qrY + $offsetY + $cornerSize, $black);
    
    $innerWhite = imagecolorallocate($image, 255, 255, 255);
    imagefilledrectangle($image, $qrX + $offsetX + 2, $qrY + $offsetY + 2, $qrX + $offsetX + $cornerSize - 2, $qrY + $offsetY + $cornerSize - 2, $innerWhite);
    imagefilledrectangle($image, $qrX + $offsetX + 4, $qrY + $offsetY + 4, $qrX + $offsetX + $cornerSize - 4, $qrY + $offsetY + $cornerSize - 4, $black);
}

function render_qrcode_from_data(GdImage $image, array $qrConfig, string $qrDataUrl): void
{
    $x = (int) ($qrConfig['x'] ?? 0);
    $y = (int) ($qrConfig['y'] ?? 0);
    $size = (int) ($qrConfig['size'] ?? 100);
    
    $halfSize = (int) ($size / 2);
    $qrX = $x - $halfSize;
    $qrY = $y - $halfSize;
    
    if (str_starts_with($qrDataUrl, 'data:image/')) {
        $base64 = preg_replace('#^data:image/[^;]+;base64,#', '', $qrDataUrl);
        $qrImage = imagecreatefromstring(base64_decode($base64));
        
        if ($qrImage) {
            $qrWidth = imagesx($qrImage);
            $qrHeight = imagesy($qrImage);
            imagecopyresampled($image, $qrImage, $qrX, $qrY, 0, 0, $size, $size, $qrWidth, $qrHeight);
            imagedestroy($qrImage);
            return;
        }
    }
    
    render_qrcode_placeholder($image, $qrConfig, '');
}

function add_watermark(GdImage $image): void
{
    $width = imagesx($image);
    $height = imagesy($image);
    
    $watermarkColor = imagecolorallocatealpha($image, 255, 255, 255, 80);
    $fontSize = 5;
    $watermarkText = 'PREVIEW - 预览专用';
    $textWidth = imagefontwidth($fontSize) * strlen($watermarkText);
    
    for ($i = 0; $i < 5; $i++) {
        for ($j = 0; $j < 8; $j++) {
            $x = $i * (int) ($width / 5) + 20;
            $y = $j * (int) ($height / 8) + 30;
            if ($i % 2 === 1) {
                $y += 40;
            }
            imagestring($image, $fontSize, $x, $y, $watermarkText, $watermarkColor);
        }
    }
}

function render_image(array $template, array $customConfig, bool $isPreview = true): GdImage|false
{
    $sourceImage = $template['source_image'] ?? '';
    if (empty($sourceImage)) {
        return false;
    }
    
    $image = load_image($sourceImage);
    if (!$image) {
        return false;
    }
    
    $targetWidth = $isPreview ? 400 : (int) ($template['canvas_width'] ?? 800);
    $targetHeight = $isPreview ? 600 : (int) ($template['canvas_height'] ?? 1200);
    
    $originalWidth = imagesx($image);
    $originalHeight = imagesy($image);
    
    if (!$isPreview && ($originalWidth !== $targetWidth || $originalHeight !== $targetHeight)) {
        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $originalWidth, $originalHeight);
        imagedestroy($image);
        $image = $resized;
    } elseif ($isPreview) {
        $resized = imagecreatetruecolor($targetWidth, $targetHeight);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $targetWidth, $targetHeight, $originalWidth, $originalHeight);
        imagedestroy($image);
        $image = $resized;
    }
    
    $scaleX = $targetWidth / (int) ($template['canvas_width'] ?? 800);
    $scaleY = $targetHeight / (int) ($template['canvas_height'] ?? 1200);
    
    $regions = $template['editable_regions'] ?? [];
    
    $themeColor = null;
    foreach ($regions as $region) {
        if ($region['region_type'] === 'color') {
            $colorKey = 'color_' . $region['id'];
            $themeColor = $customConfig[$colorKey] ?? ($region['config']['defaultValue'] ?? '#ff6b6b');
            break;
        }
    }
    
    if ($themeColor) {
        apply_color_overlay($image, $themeColor, 0.25);
    }
    
    foreach ($regions as $region) {
        $config = $region['config'];
        $regionKey = $region['region_type'] . '_' . $region['id'];
        $value = $customConfig[$regionKey] ?? ($config['defaultValue'] ?? '');
        
        $scaledConfig = $config;
        if (isset($scaledConfig['x'])) {
            $scaledConfig['x'] = (int) ($scaledConfig['x'] * $scaleX);
        }
        if (isset($scaledConfig['y'])) {
            $scaledConfig['y'] = (int) ($scaledConfig['y'] * $scaleY);
        }
        if (isset($scaledConfig['fontSize'])) {
            $scaledConfig['fontSize'] = (int) ($scaledConfig['fontSize'] * min($scaleX, $scaleY));
        }
        if (isset($scaledConfig['size'])) {
            $scaledConfig['size'] = (int) ($scaledConfig['size'] * min($scaleX, $scaleY));
        }
        
        if ($region['region_type'] === 'text' && !empty($value)) {
            render_text($image, $scaledConfig, (string) $value);
        } elseif ($region['region_type'] === 'qrcode') {
            $qrDataKey = 'qrcode_data_' . $region['id'];
            $qrImageKey = 'qrcode_image_' . $region['id'];
            
            if (isset($customConfig[$qrImageKey]) && !empty($customConfig[$qrImageKey])) {
                render_qrcode_from_data($image, $scaledConfig, $customConfig[$qrImageKey]);
            } elseif (isset($customConfig[$qrDataKey]) && !empty($customConfig[$qrDataKey])) {
                render_qrcode_placeholder($image, $scaledConfig, $customConfig[$qrDataKey]);
            } else {
                render_qrcode_placeholder($image, $scaledConfig, $value);
            }
        }
    }
    
    if ($isPreview) {
        add_watermark($image);
    }
    
    return $image;
}

function output_image(GdImage $image, string $format = 'jpg', int $quality = 90): void
{
    header('Content-Type: ' . ($format === 'png' ? 'image/png' : 'image/jpeg'));
    header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
    header('Pragma: no-cache');
    
    if ($format === 'png') {
        imagepng($image, null, 9);
    } else {
        imagejpeg($image, null, $quality);
    }
    imagedestroy($image);
}
