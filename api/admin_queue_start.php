<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/screen_service.php';

requirePanelApiAuth();

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
    startQueue($pdo, $screenId);
    $pdo->commit();
    jsonResponse(['ok' => true, 'data' => ['screen_id' => $screenId, 'source' => 'schedule']]);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    jsonResponse(['ok' => false, 'error' => 'Не удалось запустить очередь показа'], 500);
}
