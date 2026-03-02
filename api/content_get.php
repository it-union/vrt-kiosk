<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/content_repository.php';

requireTemplateApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$id = isset($_GET['content_id']) ? (int)$_GET['content_id'] : 0;
if ($id <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Нужен content_id'], 400);
}

try {
    $row = contentGet(dbMysql(), $id);
    if ($row === null) {
        jsonResponse(['ok' => false, 'error' => 'Контент не найден'], 404);
    }
    jsonResponse(['ok' => true, 'data' => $row]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось получить контент'], 500);
}
