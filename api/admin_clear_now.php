<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/screen_service.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$screenId = isset($_POST['screen_id']) ? (int)$_POST['screen_id'] : 0;
if ($screenId <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Нужен screen_id'], 400);
}

$pdo = dbMysql();

try {
    $pdo->beginTransaction();
    clearNow($pdo, $screenId);
    $pdo->commit();

    jsonResponse(['ok' => true, 'data' => ['screen_id' => $screenId]]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    jsonResponse(['ok' => false, 'error' => 'Не удалось снять ручной режим'], 500);
}
