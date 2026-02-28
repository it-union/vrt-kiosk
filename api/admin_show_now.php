<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/screen_service.php';

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$screenId = isset($_POST['screen_id']) ? (int)$_POST['screen_id'] : 0;
$contentId = isset($_POST['content_id']) ? (int)$_POST['content_id'] : 0;
$duration = isset($_POST['duration_minutes']) ? (int)$_POST['duration_minutes'] : 10;

if ($screenId <= 0 || $contentId <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Нужны screen_id и content_id'], 400);
}

$duration = max(1, min(1440, $duration));

$pdo = dbMysql();

try {
    $pdo->beginTransaction();
    $commandId = showNow($pdo, $screenId, $contentId, $duration);
    $pdo->commit();

    jsonResponse([
        'ok' => true,
        'data' => [
            'screen_id' => $screenId,
            'command_id' => $commandId,
            'duration_minutes' => $duration,
        ],
    ]);
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e->getMessage() === 'content_not_found') {
        jsonResponse(['ok' => false, 'error' => 'Контент не найден или неактивен'], 404);
    }

    jsonResponse(['ok' => false, 'error' => 'Не удалось создать ручную команду'], 500);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    jsonResponse(['ok' => false, 'error' => 'Не удалось создать ручную команду'], 500);
}
