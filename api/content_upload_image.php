<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

if (!isset($_FILES['image']) || !is_array($_FILES['image'])) {
    jsonResponse(['ok' => false, 'error' => 'Файл не передан'], 400);
}

$file = $_FILES['image'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    jsonResponse(['ok' => false, 'error' => 'Ошибка загрузки файла'], 400);
}

$tmpPath = (string)($file['tmp_name'] ?? '');
if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
    jsonResponse(['ok' => false, 'error' => 'Некорректный источник файла'], 400);
}

$size = (int)($file['size'] ?? 0);
if ($size <= 0 || $size > 20 * 1024 * 1024) {
    jsonResponse(['ok' => false, 'error' => 'Размер файла должен быть от 1 байта до 20 МБ'], 400);
}

$origName = (string)($file['name'] ?? '');
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
$allowedExt = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
if (!in_array($ext, $allowedExt, true)) {
    jsonResponse(['ok' => false, 'error' => 'Недопустимый формат файла'], 400);
}

$originalBase = trim((string)pathinfo($origName, PATHINFO_FILENAME));
$safeBase = preg_replace('/[^\\p{L}\\p{N}\\-_\\s]+/u', '', $originalBase);
$safeBase = preg_replace('/\\s+/u', '_', (string)$safeBase);
$safeBase = trim((string)$safeBase, '._- ');
if ($safeBase === '') {
    $safeBase = 'file';
}
if (function_exists('mb_substr')) {
    $safeBase = mb_substr($safeBase, 0, 80, 'UTF-8');
} else {
    $safeBase = substr($safeBase, 0, 80);
}


$uploadsDir = realpath(__DIR__ . '/..');
if ($uploadsDir === false) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось определить каталог проекта'], 500);
}
$targetDir = $uploadsDir . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content_images';
if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось создать каталог для загрузки'], 500);
}

$rand = '';
try {
    $rand = bin2hex(random_bytes(4));
} catch (Throwable $e) {
    $rand = (string)mt_rand(10000000, 99999999);
}
$baseName = $safeBase . '_' . date('Ymd_His') . '_' . $rand;
$targetPath = $targetDir . DIRECTORY_SEPARATOR . $baseName . '.' . $ext;
if (!move_uploaded_file($tmpPath, $targetPath)) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось сохранить файл'], 500);
}

$url = '/uploads/content_images/' . basename($targetPath);
jsonResponse([
    'ok' => true,
    'data' => [
        'url' => $url,
        'size' => $size,
        'name' => basename($targetPath),
    ],
]);
