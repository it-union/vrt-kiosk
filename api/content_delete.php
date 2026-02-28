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
    $ok = contentDelete(dbMysql(), $id);
    if (!$ok) {
        jsonResponse(['ok' => false, 'error' => 'Контент не найден'], 404);
    }
    jsonResponse(['ok' => true, 'data' => ['content_id' => $id]]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось удалить контент'], 500);
}
