<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/db_mysql.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$url = trim((string)($_POST['url'] ?? ''));
$currentContentId = isset($_POST['current_content_id']) ? (int)$_POST['current_content_id'] : 0;
if ($url === '') {
    jsonResponse(['ok' => false, 'error' => 'Нужен url'], 400);
}

$prefix = '/uploads/content_ppt/';
if (substr($url, 0, strlen($prefix)) !== $prefix) {
    jsonResponse(['ok' => false, 'error' => 'Удаление возможно только из папки библиотеки'], 400);
}

$encodedName = substr($url, strlen($prefix));
$decodedName = rawurldecode($encodedName);
$fileName = basename($decodedName);
if ($fileName === '' || $fileName !== $decodedName) {
    jsonResponse(['ok' => false, 'error' => 'Некорректное имя файла'], 400);
}

try {
    $pdo = dbMysql();
    $sql = 'SELECT COUNT(*) FROM content_items WHERE media_url = :url';
    $params = ['url' => $url];
    if ($currentContentId > 0) {
        $sql .= ' AND id <> :current_id';
        $params['current_id'] = $currentContentId;
    }
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $linksCount = (int)$stmt->fetchColumn();
    if ($linksCount > 0) {
        jsonResponse(['ok' => false, 'error' => 'Файл используется в других контентах и не может быть удален'], 409);
    }

    $root = realpath(__DIR__ . '/..');
    if ($root === false) {
        jsonResponse(['ok' => false, 'error' => 'Не удалось определить каталог проекта'], 500);
    }

    $dir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content_ppt';
    $path = $dir . DIRECTORY_SEPARATOR . $fileName;
    if (!is_file($path)) {
        jsonResponse(['ok' => false, 'error' => 'Файл не найден'], 404);
    }
    if (!unlink($path)) {
        jsonResponse(['ok' => false, 'error' => 'Не удалось удалить файл'], 500);
    }

    $baseName = pathinfo($fileName, PATHINFO_FILENAME);
    $previewDir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content_ppt_preview';
    if (is_dir($previewDir)) {
        $previewFiles = @glob($previewDir . DIRECTORY_SEPARATOR . $baseName . '*.png') ?: [];
        foreach ($previewFiles as $previewPath) {
            if (is_file($previewPath)) @unlink($previewPath);
        }
    }

    $metaPath = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'content_ppt_meta' . DIRECTORY_SEPARATOR . $baseName . '.json';
    if (is_file($metaPath)) @unlink($metaPath);

    jsonResponse(['ok' => true, 'data' => ['url' => $url, 'deleted' => true]]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось удалить файл'], 500);
}

