<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/template_service.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$id = isset($_POST['template_id']) ? (int)$_POST['template_id'] : 0;
if ($id <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Нужен template_id'], 400);
}

try {
    deleteTemplate(dbMysql(), $id);
    jsonResponse(['ok' => true, 'data' => ['template_id' => $id]]);
} catch (RuntimeException $e) {
    if ($e->getMessage() === 'template_not_found') {
        jsonResponse(['ok' => false, 'error' => 'Шаблон не найден'], 404);
    }
    if ($e->getMessage() === 'template_in_use') {
        jsonResponse(['ok' => false, 'error' => 'Шаблон используется в активном расписании и не может быть удален'], 409);
    }
    jsonResponse(['ok' => false, 'error' => 'Не удалось удалить шаблон'], 500);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось удалить шаблон'], 500);
}
