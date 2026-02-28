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

$dir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content_videos';
if (!is_dir($dir)) {
    jsonResponse(['ok' => true, 'data' => []]);
}

$allowed = ['mp4', 'webm', 'ogg', 'ogv', 'mov', 'm4v'];
$files = @scandir($dir);
if (!is_array($files)) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось прочитать каталог видео'], 500);
}

$items = [];
foreach ($files as $name) {
    if ($name === '.' || $name === '..') {
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
        'url' => '/uploads/content_videos/' . rawurlencode($name),
        'size' => $size !== false ? (int)$size : 0,
        'mtime' => $mtime !== false ? (int)$mtime : 0,
        'kind' => 'video',
    ];
}

usort($items, static function (array $a, array $b): int {
    return ($b['mtime'] <=> $a['mtime']);
});

jsonResponse(['ok' => true, 'data' => $items]);
