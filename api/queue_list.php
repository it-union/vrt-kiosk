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
    jsonResponse(['ok' => true, 'data' => queueListAll(dbMysql())]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось загрузить список очередей'], 500);
}
