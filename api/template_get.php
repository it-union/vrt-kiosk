<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/template_service.php';
require_once __DIR__ . '/../modules/entity_permission_repository.php';

requireTemplateApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$id = isset($_GET['template_id']) ? (int)$_GET['template_id'] : 0;
if ($id <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Нужен template_id'], 400);
}

try {
    $pdo = dbMysql();
    $item = getTemplateWithBlocks($pdo, $id);
    if ($item === null) {
        jsonResponse(['ok' => false, 'error' => 'Шаблон не найден'], 404);
    }

    $currentUserId = authCurrentUserId();
    $isAdmin = authIsAdministrator();
    $ownerId = isset($item['created_by']) ? (int)$item['created_by'] : 0;
    $canManage = $isAdmin || ($ownerId > 0 && $ownerId === $currentUserId);
    if (!$canManage && $currentUserId > 0) {
        $canManage = entityPermissionHasAny($pdo, 'template', $id, $currentUserId);
    }
    $item['can_manage'] = $canManage ? 1 : 0;
    $item['can_delete'] = ($isAdmin || ($ownerId > 0 && $ownerId === $currentUserId)) ? 1 : 0;

    jsonResponse(['ok' => true, 'data' => $item]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось загрузить шаблон'], 500);
}
