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

$queueId = isset($_POST['queue_id']) ? (int)$_POST['queue_id'] : 0;
if ($queueId <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Нужен queue_id'], 400);
}

try {
    $pdo = dbMysql();
    $queue = queueGetById($pdo, $queueId);
    if ($queue === null) {
        jsonResponse(['ok' => false, 'error' => 'Очередь не найдена'], 404);
    }
    queueActivate($pdo, $queueId);
    jsonResponse(['ok' => true, 'data' => ['queue_id' => $queueId]]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось активировать очередь'], 500);
}
