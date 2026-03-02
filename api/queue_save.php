<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/queue_repository.php';

requirePanelApiAuth();

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
    $normalized[] = [
        'template_id' => $templateId,
        'duration_sec' => $durationSec,
    ];
}

try {
    $pdo = dbMysql();
    $queueId = isset($_POST['queue_id']) ? (int)$_POST['queue_id'] : 0;
    $queue = queueGetOrActive($pdo, $queueId);
    if ($queue === null) {
        jsonResponse(['ok' => false, 'error' => 'Активная очередь не найдена'], 404);
    }

    $queueName = trim((string)($_POST['queue_name'] ?? (string)($queue['name'] ?? '')));
    $queueActive = (int)($_POST['queue_is_active'] ?? 0) === 1;

    queueUpdateMeta($pdo, (int)$queue['id'], $queueName, $queueActive);
    queueSaveItems($pdo, (int)$queue['id'], $normalized);

    jsonResponse([
        'ok' => true,
        'data' => [
            'queue_id' => (int)$queue['id'],
            'queue_name' => $queueName,
            'queue_is_active' => $queueActive ? 1 : 0,
            'items_count' => count($normalized),
        ],
    ]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось сохранить очередь'], 500);
}
