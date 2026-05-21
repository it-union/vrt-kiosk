<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/template_service.php';
require_once __DIR__ . '/../modules/entity_permission_repository.php';
require_once __DIR__ . '/../modules/user_repository.php';

requireTemplateApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'GET') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

try {
    $pdo = dbMysql();
    $rows = listTemplatesWithActiveMark($pdo);
    $users = userListAllWithRole($pdo);
    $userLabelById = [];
    foreach ($users as $u) {
        $uid = (int)($u['id'] ?? 0);
        if ($uid <= 0) {
            continue;
        }
        $label = trim((string)($u['full_name'] ?? ''));
        if ($label === '') {
            $label = trim((string)($u['login'] ?? ''));
        }
        if ($label === '') {
            $label = 'ID ' . $uid;
        }
        $userLabelById[$uid] = $label;
    }
    $currentUserId = authCurrentUserId();
    $isAdmin = authIsAdministrator();
    foreach ($rows as &$row) {
        $ownerId = isset($row['created_by']) ? (int)$row['created_by'] : 0;
        $id = isset($row['id']) ? (int)$row['id'] : 0;
        $canManage = $isAdmin || ($ownerId > 0 && $ownerId === $currentUserId);
        if (!$canManage && $currentUserId > 0 && $id > 0) {
            $canManage = entityPermissionHasAny($pdo, 'template', $id, $currentUserId);
        }
        $row['can_manage'] = $canManage ? 1 : 0;
        $row['can_delete'] = ($isAdmin || ($ownerId > 0 && $ownerId === $currentUserId)) ? 1 : 0;
        $sharedWith = [];
        if ($id > 0) {
            $permRows = entityPermissionListForEntity($pdo, 'template', $id);
            foreach ($permRows as $permRow) {
                $permUserId = (int)($permRow['user_id'] ?? 0);
                if ($permUserId <= 0 || $permUserId === $ownerId) {
                    continue;
                }
                $sharedWith[] = $userLabelById[$permUserId] ?? ('ID ' . $permUserId);
            }
        }
        $row['shared_with'] = array_values(array_unique($sharedWith));
    }
    unset($row);
    jsonResponse(['ok' => true, 'data' => $rows]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось загрузить шаблоны'], 500);
}
