<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/doctor_repository.php';

requireAdministratorApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$rowId = isset($_POST['row_id']) ? (int)$_POST['row_id'] : 0;
if ($rowId <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Некорректный row_id'], 400);
}

try {
    $pdo = dbMysql();
    $ok = doctorDelete($pdo, $rowId);
    if (!$ok) {
        jsonResponse(['ok' => false, 'error' => 'Врач не найден'], 404);
    }
    jsonResponse(['ok' => true, 'data' => ['row_id' => $rowId]]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось удалить доктора'], 500);
}
