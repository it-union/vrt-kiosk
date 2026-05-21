<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/content_repository.php';
require_once __DIR__ . '/../modules/entity_permission_repository.php';

requireTemplateApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$id = isset($_GET['content_id']) ? (int)$_GET['content_id'] : 0;
if ($id <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Нужен content_id'], 400);
}

try {
    $pdo = dbMysql();
    $row = contentGet($pdo, $id);
    if ($row === null) {
        jsonResponse(['ok' => false, 'error' => 'Контент не найден'], 404);
    }

    $currentUserId = authCurrentUserId();
    $isAdmin = authIsAdministrator();
    $ownerId = isset($row['created_by']) ? (int)$row['created_by'] : 0;
    $canManage = $isAdmin || ($ownerId > 0 && $ownerId === $currentUserId);
    if (!$canManage && $currentUserId > 0) {
        $canManage = entityPermissionHasAny($pdo, 'content', $id, $currentUserId);
    }
    $row['can_manage'] = $canManage ? 1 : 0;
    $row['can_delete'] = ($isAdmin || ($ownerId > 0 && $ownerId === $currentUserId)) ? 1 : 0;

    jsonResponse(['ok' => true, 'data' => $row]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось получить контент'], 500);
}
