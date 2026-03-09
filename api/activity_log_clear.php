<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../modules/activity_log_repository.php';
require_once __DIR__ . '/../core/db_mysql.php';

requireTemplateApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$currentUser = authCurrentUser();
if (!$currentUser || $currentUser['role_code'] !== 'administrator') {
    jsonResponse(['ok' => false, 'error' => 'Доступ запрещён'], 403);
}

$pdo = dbMysql();
activityLogEnsureSchema($pdo);

try {
    activityLogTruncate($pdo);
    
    // Логируем очистку
    activityLogCreate($pdo, (int)$currentUser['id'], 'logs_clear', 'Очистка журнала активности');
    
    jsonResponse(['ok' => true]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => $e->getMessage()], 500);
}