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

try {
    $queueId = isset($_POST['queue_id']) ? (int)$_POST['queue_id'] : 0;
    if ($queueId <= 0) {
        jsonResponse(['ok' => false, 'error' => 'Некорректный идентификатор очереди'], 400);
    }

    $pdo = dbMysql();
    $queue = queueGetById($pdo, $queueId);
    if ($queue === null) {
        jsonResponse(['ok' => false, 'error' => 'Очередь не найдена'], 404);
    }

    $nextQueueId = queueDelete($pdo, $queueId);

    $userId = (int)($currentUser['id'] ?? 0);
    $queueName = (string)($queue['name'] ?? '');
    activityLogCreate($pdo, $userId, 'queue_delete', 'Удаление очереди', 'queue', $queueId, $queueName);

    jsonResponse([
        'ok' => true,
        'data' => [
            'deleted_queue_id' => $queueId,
            'next_queue_id' => $nextQueueId,
        ],
    ]);
} catch (RuntimeException $e) {
    $message = $e->getMessage();
    if ($message === 'queue_not_found') {
        jsonResponse(['ok' => false, 'error' => 'Очередь не найдена'], 404);
    }
    if ($message === 'queue_active_delete_forbidden') {
        jsonResponse(['ok' => false, 'error' => 'Активную очередь удалять нельзя'], 400);
    }
    if ($message === 'queue_last_test_delete_forbidden') {
        jsonResponse(['ok' => false, 'error' => 'Нельзя удалить последнюю тестовую очередь'], 400);
    }
    jsonResponse(['ok' => false, 'error' => 'Не удалось удалить очередь'], 500);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось удалить очередь'], 500);
}

