<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/queue_repository.php';
require_once __DIR__ . '/../modules/activity_log_repository.php';

requirePanelApiAuth();

$currentUser = authCurrentUser();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$rawItems = (string)($_POST['items_json'] ?? '[]');
$items = json_decode($rawItems, true);
if (!is_array($items)) {
    jsonResponse(['ok' => false, 'error' => 'Некорректный items_json'], 400);
}

$normalized = [];
foreach ($items as $item) {
    if (!is_array($item)) {
        continue;
    }
    $templateId = (int)($item['template_id'] ?? 0);
    $durationSec = (int)($item['duration_sec'] ?? 0);
    if ($templateId <= 0 || $durationSec <= 0) {
        continue;
    }
    $isActive = array_key_exists('is_active', $item) ? ((int)$item['is_active'] === 1 ? 1 : 0) : 1;
    $normalized[] = [
        'template_id' => $templateId,
        'is_active' => $isActive,
        'duration_sec' => $durationSec,
    ];
}

try {
    $pdo = dbMysql();
    $queueId = isset($_POST['queue_id']) ? (int)$_POST['queue_id'] : 0;
    $queue = queueGetOrActive($pdo, $queueId);
    if ($queue === null) {
        jsonResponse(['ok' => false, 'error' => 'Очередь не найдена'], 404);
    }

    $queueName = trim((string)($_POST['queue_name'] ?? (string)($queue['name'] ?? '')));
    $queueType = queueNormalizeType((string)($_POST['queue_type'] ?? (string)($queue['queue_type'] ?? 'archive')));

    queueUpdateMeta($pdo, (int)$queue['id'], $queueName, $queueType);
    queueSaveItems($pdo, (int)$queue['id'], $normalized);

    // Логирование
    $userId = (int)($currentUser['id'] ?? 0);
    $actionType = isset($_POST['queue_id']) && (int)$_POST['queue_id'] > 0 ? 'queue_save' : 'queue_create';
    $description = $actionType === 'queue_save' ? 'Сохранение очереди' : 'Создание очереди';
    activityLogCreate($pdo, $userId, $actionType, $description, 'queue', (int)$queue['id'], $queueName);

    jsonResponse([
        'ok' => true,
        'data' => [
            'queue_id' => (int)$queue['id'],
            'queue_name' => $queueName,
            'queue_type' => $queueType,
            'items_count' => count($normalized),
        ],
    ]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось сохранить очередь'], 500);
}
