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

try {
    $pdo = dbMysql();
    $name = trim((string)($_POST['name'] ?? ''));
    if ($name === '') {
        $name = queueGenerateName($pdo);
    }
    $queueId = queueCreate($pdo, $name);
    jsonResponse(['ok' => true, 'data' => ['queue_id' => $queueId, 'name' => $name]]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось создать очередь'], 500);
}
