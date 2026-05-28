<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';

requireTemplateApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

function normalizeVideoBaseName(string $origName): string
{
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
    return $safeBase;
}

function ensureVideoTargetDir(): array
{
    $root = realpath(__DIR__ . '/..');
    if ($root === false) {
        jsonResponse(['ok' => false, 'error' => 'Не удалось определить каталог проекта'], 500);
    }
    $targetDir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content_videos';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true) && !is_dir($targetDir)) {
        jsonResponse(['ok' => false, 'error' => 'Не удалось создать каталог для загрузки'], 500);
    }
    return [$root, $targetDir];
}

function buildVideoTargetPath(string $targetDir, string $safeBase, string $ext): array
{
    try {
        $rand = bin2hex(random_bytes(4));
    } catch (Throwable $e) {
        $rand = (string)mt_rand(10000000, 99999999);
    }
    $baseName = $safeBase . '_' . date('Ymd_His') . '_' . $rand;
    return [$targetDir . DIRECTORY_SEPARATOR . $baseName . '.' . $ext, $baseName];
}

$allowedExt = ['mp4', 'webm', 'ogg', 'ogv', 'mov', 'm4v'];
$isChunkUpload = ((int)($_POST['chunk_upload'] ?? 0)) === 1;

if ($isChunkUpload) {
    if (!isset($_FILES['chunk']) || !is_array($_FILES['chunk'])) {
        jsonResponse(['ok' => false, 'error' => 'Часть файла не передана'], 400);
    }
    $file = $_FILES['chunk'];
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        jsonResponse(['ok' => false, 'error' => 'Ошибка загрузки части файла'], 400);
    }
    $tmpPath = (string)($file['tmp_name'] ?? '');
    if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
        jsonResponse(['ok' => false, 'error' => 'Некорректный источник части файла'], 400);
    }

    $chunkIndex = max(0, (int)($_POST['chunk_index'] ?? -1));
    $chunkTotal = (int)($_POST['chunk_total'] ?? 0);
    if ($chunkTotal <= 0 || $chunkIndex >= $chunkTotal) {
        jsonResponse(['ok' => false, 'error' => 'Некорректные параметры чанков'], 400);
    }
    $uploadId = trim((string)($_POST['upload_id'] ?? ''));
    if ($uploadId === '' || preg_match('/^[A-Za-z0-9_-]{8,80}$/', $uploadId) !== 1) {
        jsonResponse(['ok' => false, 'error' => 'Некорректный upload_id'], 400);
    }
    $origName = trim((string)($_POST['original_name'] ?? 'video.mp4'));
    $totalSize = max(0, (int)($_POST['total_size'] ?? 0));
    if ($totalSize <= 0 || $totalSize > 2 * 1024 * 1024 * 1024) {
        jsonResponse(['ok' => false, 'error' => 'Некорректный total_size'], 400);
    }

    $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
    if (!in_array($ext, $allowedExt, true)) {
        jsonResponse(['ok' => false, 'error' => 'Недопустимый формат файла'], 400);
    }

    $safeBase = normalizeVideoBaseName($origName);
    [$root, $targetDir] = ensureVideoTargetDir();
    $chunkRoot = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content_videos_chunks';
    $uploadDir = $chunkRoot . DIRECTORY_SEPARATOR . $uploadId;
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0775, true) && !is_dir($uploadDir)) {
        jsonResponse(['ok' => false, 'error' => 'Не удалось подготовить хранилище чанков'], 500);
    }

    $chunkPath = $uploadDir . DIRECTORY_SEPARATOR . sprintf('part_%06d.chunk', $chunkIndex);
    if (!move_uploaded_file($tmpPath, $chunkPath)) {
        jsonResponse(['ok' => false, 'error' => 'Не удалось сохранить часть файла'], 500);
    }

    $metaPath = $uploadDir . DIRECTORY_SEPARATOR . 'meta.json';
    $meta = [
        'original_name' => $origName,
        'safe_base' => $safeBase,
        'ext' => $ext,
        'chunk_total' => $chunkTotal,
        'total_size' => $totalSize,
    ];
    file_put_contents($metaPath, json_encode($meta, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES), LOCK_EX);

    $complete = true;
    for ($i = 0; $i < $chunkTotal; $i++) {
        if (!is_file($uploadDir . DIRECTORY_SEPARATOR . sprintf('part_%06d.chunk', $i))) {
            $complete = false;
            break;
        }
    }

    if (!$complete) {
        jsonResponse([
            'ok' => true,
            'data' => [
                'complete' => false,
                'chunk_index' => $chunkIndex,
                'chunk_total' => $chunkTotal,
            ],
        ]);
    }

    [$targetPath, $baseName] = buildVideoTargetPath($targetDir, $safeBase, $ext);
    $out = fopen($targetPath, 'wb');
    if ($out === false) {
        jsonResponse(['ok' => false, 'error' => 'Не удалось открыть целевой файл'], 500);
    }
    $written = 0;
    for ($i = 0; $i < $chunkTotal; $i++) {
        $part = $uploadDir . DIRECTORY_SEPARATOR . sprintf('part_%06d.chunk', $i);
        $in = fopen($part, 'rb');
        if ($in === false) {
            fclose($out);
            @unlink($targetPath);
            jsonResponse(['ok' => false, 'error' => 'Не удалось собрать файл'], 500);
        }
        while (!feof($in)) {
            $buffer = fread($in, 1024 * 1024);
            if (!is_string($buffer) || $buffer === '') {
                continue;
            }
            $written += strlen($buffer);
            fwrite($out, $buffer);
        }
        fclose($in);
    }
    fclose($out);

    if ($written <= 0) {
        @unlink($targetPath);
        jsonResponse(['ok' => false, 'error' => 'Пустой файл после сборки'], 500);
    }

    for ($i = 0; $i < $chunkTotal; $i++) {
        @unlink($uploadDir . DIRECTORY_SEPARATOR . sprintf('part_%06d.chunk', $i));
    }
    @unlink($metaPath);
    @rmdir($uploadDir);

    $url = '/uploads/content_videos/' . basename($targetPath);
    jsonResponse([
        'ok' => true,
        'data' => [
            'complete' => true,
            'url' => $url,
            'size' => $written,
            'name' => basename($targetPath),
        ],
    ]);
}

if (!isset($_FILES['video']) || !is_array($_FILES['video'])) {
    jsonResponse(['ok' => false, 'error' => 'Файл не передан'], 400);
}

$file = $_FILES['video'];
if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    jsonResponse(['ok' => false, 'error' => 'Ошибка загрузки файла'], 400);
}

$tmpPath = (string)($file['tmp_name'] ?? '');
if ($tmpPath === '' || !is_uploaded_file($tmpPath)) {
    jsonResponse(['ok' => false, 'error' => 'Некорректный источник файла'], 400);
}

$size = (int)($file['size'] ?? 0);
if ($size <= 0 || $size > 250 * 1024 * 1024) {
    jsonResponse(['ok' => false, 'error' => 'Размер файла должен быть от 1 байта до 250 МБ'], 400);
}

$origName = (string)($file['name'] ?? '');
$ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
if (!in_array($ext, $allowedExt, true)) {
    jsonResponse(['ok' => false, 'error' => 'Недопустимый формат файла'], 400);
}

$safeBase = normalizeVideoBaseName($origName);
[, $targetDir] = ensureVideoTargetDir();
[$targetPath, $baseName] = buildVideoTargetPath($targetDir, $safeBase, $ext);
if (!move_uploaded_file($tmpPath, $targetPath)) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось сохранить файл'], 500);
}

$url = '/uploads/content_videos/' . basename($targetPath);
jsonResponse([
    'ok' => true,
    'data' => [
        'url' => $url,
        'size' => $size,
        'name' => basename($targetPath),
    ],
]);

