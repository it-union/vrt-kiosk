<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/db_mysql.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$url = trim((string)($_POST['url'] ?? ''));
if ($url === '') {
    jsonResponse(['ok' => false, 'error' => 'Нужен url изображения'], 400);
}

$prefix = '/uploads/template_images/';
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

    $stmt1 = $pdo->prepare('SELECT COUNT(*) FROM templates WHERE layout_json LIKE :needle');
    $stmt1->execute(['needle' => '%' . $url . '%']);
    $usedInTemplates = (int)$stmt1->fetchColumn();

    $stmt2 = $pdo->prepare('SELECT COUNT(*) FROM template_blocks WHERE style_json LIKE :needle');
    $stmt2->execute(['needle' => '%' . $url . '%']);
    $usedInBlocks = (int)$stmt2->fetchColumn();

    if (($usedInTemplates + $usedInBlocks) > 0) {
        jsonResponse(['ok' => false, 'error' => 'Изображение используется в шаблонах и не может быть удалено'], 409);
    }

    $root = realpath(__DIR__ . '/..');
    if ($root === false) {
        jsonResponse(['ok' => false, 'error' => 'Не удалось определить каталог проекта'], 500);
    }
    $dir = $root . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR . 'template_images';
    $path = $dir . DIRECTORY_SEPARATOR . $fileName;
    if (!is_file($path)) {
        jsonResponse(['ok' => false, 'error' => 'Файл не найден'], 404);
    }
    if (!unlink($path)) {
        jsonResponse(['ok' => false, 'error' => 'Не удалось удалить файл'], 500);
    }

    jsonResponse(['ok' => true, 'data' => ['url' => $url, 'deleted' => true]]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось удалить изображение'], 500);
}

