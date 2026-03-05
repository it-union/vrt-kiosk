<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/content_repository.php';

requireTemplateApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$type = trim((string)($_GET['type'] ?? ''));
$allowedTypes = ['text', 'image', 'html', 'video', 'ppt', 'schedule'];
if ($type !== '' && !in_array($type, $allowedTypes, true)) {
    jsonResponse(['ok' => false, 'error' => 'Недопустимый type'], 400);
}

$active = isset($_GET['active']) ? (int)$_GET['active'] : -1;
$activeFilter = null;
if ($active === 0 || $active === 1) {
    $activeFilter = $active;
}

try {
    $rows = contentList(dbMysql(), $type !== '' ? $type : null, $activeFilter);
    jsonResponse(['ok' => true, 'data' => $rows]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось получить список контента'], 500);
}
