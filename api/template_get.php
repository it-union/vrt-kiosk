<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/template_service.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$id = isset($_GET['template_id']) ? (int)$_GET['template_id'] : 0;
if ($id <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Нужен template_id'], 400);
}

try {
    $item = getTemplateWithBlocks(dbMysql(), $id);
    if ($item === null) {
        jsonResponse(['ok' => false, 'error' => 'Шаблон не найден'], 404);
    }
    jsonResponse(['ok' => true, 'data' => $item]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось загрузить шаблон'], 500);
}
