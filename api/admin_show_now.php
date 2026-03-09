<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/screen_service.php';
require_once __DIR__ . '/../modules/activity_log_repository.php';

requirePanelApiAuth();

$currentUser = authCurrentUser();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$deviceKey = isset($_POST['device_key']) ? normalizeScreenDeviceKey((string)$_POST['device_key']) : '';
$screenId = isset($_POST['screen_id']) ? (int)$_POST['screen_id'] : -1;
$contentId = isset($_POST['content_id']) ? (int)$_POST['content_id'] : 0;
$durationMinutes = isset($_POST['duration_minutes']) ? max(1, (int)$_POST['duration_minutes']) : 10;
if ($deviceKey !== '') {
    $screenId = publicScreenIdByDeviceKey($deviceKey);
}

if ($screenId < 0 || $contentId <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Нужны device_key и content_id'], 400);
}
$deviceKey = deviceKeyByPublicScreenId($screenId);
$pdo = dbMysql();
activityLogEnsureSchema($pdo);

try {
    $pdo->beginTransaction();
    $commandId = showNow($pdo, $screenId, $contentId, $durationMinutes);
    $pdo->commit();

    // Логирование
    $userId = (int)($currentUser['id'] ?? 0);
    activityLogCreate($pdo, $userId, 'queue_manual', 'Включение ручного режима', 'queue', $screenId);

    jsonResponse([
        'ok' => true,
        'data' => [
            'screen_id' => $screenId,
            'device_key' => $deviceKey,
            'content_id' => $contentId,
            'command_id' => $commandId,
        ],
    ]);
} catch (RuntimeException $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    if ($e->getMessage() === 'content_not_found') {
        jsonResponse(['ok' => false, 'error' => 'Контент не найден'], 404);
    }

    jsonResponse(['ok' => false, 'error' => 'Не удалось включить ручной показ'], 500);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }

    jsonResponse(['ok' => false, 'error' => 'Не удалось включить ручной показ'], 500);
}
