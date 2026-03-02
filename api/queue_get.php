<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/queue_repository.php';

requirePanelApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

try {
    $pdo = dbMysql();
    $queueId = isset($_GET['queue_id']) ? (int)$_GET['queue_id'] : 0;
    $queue = queueGetOrActive($pdo, $queueId);
    if ($queue === null) {
        jsonResponse(['ok' => false, 'error' => 'Активная очередь не найдена'], 404);
    }

    $items = queueGetItems($pdo, (int)$queue['id']);
    jsonResponse([
        'ok' => true,
        'data' => [
            'queue' => $queue,
            'items' => $items,
        ],
    ]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось загрузить очередь'], 500);
}
