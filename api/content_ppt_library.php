<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$root = realpath(__DIR__ . '/..');
if ($root === false) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось определить каталог проекта'], 500);
}

$dir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content_ppt';
if (!is_dir($dir)) {
    jsonResponse(['ok' => true, 'data' => []]);
}

$allowed = ['pdf'];
$files = @scandir($dir);
if (!is_array($files)) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось прочитать каталог презентаций'], 500);
}

$items = [];
foreach ($files as $name) {
    if ($name === '.' || $name === '..' || $name === '_src') {
        continue;
    }
    $full = $dir . DIRECTORY_SEPARATOR . $name;
    if (!is_file($full)) {
        continue;
    }
    $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowed, true)) {
        continue;
    }
    $mtime = @filemtime($full);
    $size = @filesize($full);
    $items[] = [
        'name' => $name,
        'url' => '/uploads/content_ppt/' . rawurlencode($name),
        'size' => $size !== false ? (int)$size : 0,
        'mtime' => $mtime !== false ? (int)$mtime : 0,
        'kind' => 'ppt',
        'preview_url' => '',
    ];
}

$metaDir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content_ppt_meta';
if (is_dir($metaDir)) {
    foreach ($items as &$item) {
        $baseName = pathinfo((string)$item['name'], PATHINFO_FILENAME);
        $metaPath = $metaDir . DIRECTORY_SEPARATOR . $baseName . '.json';
        if (!is_file($metaPath)) continue;
        $rawMeta = @file_get_contents($metaPath);
        $meta = is_string($rawMeta) ? json_decode($rawMeta, true) : null;
        if (!is_array($meta)) continue;
        $previewUrl = isset($meta['preview_url']) && is_string($meta['preview_url']) ? $meta['preview_url'] : '';
        if ($previewUrl === '' && isset($meta['preview_pages']) && is_array($meta['preview_pages']) && isset($meta['preview_pages'][0]) && is_string($meta['preview_pages'][0])) {
            $previewUrl = $meta['preview_pages'][0];
        }
        $item['preview_url'] = $previewUrl;
    }
    unset($item);
}

usort($items, static function (array $a, array $b): int {
    return ($b['mtime'] <=> $a['mtime']);
});

jsonResponse(['ok' => true, 'data' => $items]);
