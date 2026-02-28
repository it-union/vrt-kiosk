<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/content_repository.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$id = isset($_POST['content_id']) ? (int)$_POST['content_id'] : 0;
if ($id <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Нужен content_id'], 400);
}

try {
    $pdo = dbMysql();
    $src = contentGet($pdo, $id);
    if ($src === null) {
        jsonResponse(['ok' => false, 'error' => 'Контент не найден'], 404);
    }

    $title = trim((string)($src['title'] ?? ''));
    $newTitle = $title === '' ? 'Контент (копия)' : ($title . ' (копия)');

    $srcType = ($src['type'] ?? null) !== null ? (string)$src['type'] : 'image';

    $newId = contentCreate($pdo, [
        'type' => $srcType,
        'title' => $newTitle,
        'body' => ($src['body'] ?? null) !== null ? (string)$src['body'] : null,
        'data_json' => ($src['data_json'] ?? null) !== null ? (string)$src['data_json'] : null,
        'media_url' => ($src['media_url'] ?? null) !== null ? (string)$src['media_url'] : null,
        'is_active' => (int)($src['is_active'] ?? 1),
        'publish_from' => ($src['publish_from'] ?? null) !== null ? (string)$src['publish_from'] : null,
        'publish_to' => ($src['publish_to'] ?? null) !== null ? (string)$src['publish_to'] : null,
    ]);

    jsonResponse(['ok' => true, 'data' => ['content_id' => $newId]]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось дублировать контент'], 500);
}
