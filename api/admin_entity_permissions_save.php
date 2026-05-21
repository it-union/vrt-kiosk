<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/user_repository.php';
require_once __DIR__ . '/../modules/template_repository.php';
require_once __DIR__ . '/../modules/content_repository.php';
require_once __DIR__ . '/../modules/entity_permission_repository.php';
require_once __DIR__ . '/../modules/activity_log_repository.php';

requireAdministratorApiAuth();

if (($_SERVER['REQUEST_METHOD'] ?? '') !== 'POST') {
    jsonResponse(['ok' => false, 'error' => 'Метод не поддерживается'], 405);
}

$entityType = trim((string)($_POST['entity_type'] ?? ''));
$entityId = isset($_POST['entity_id']) ? (int)$_POST['entity_id'] : 0;
$editorUserId = isset($_POST['editor_user_id']) ? (int)$_POST['editor_user_id'] : 0;
$action = trim((string)($_POST['action'] ?? ''));
$includeLinkedContent = isset($_POST['include_linked_content']) && (int)$_POST['include_linked_content'] === 1;

if (!entityPermissionIsValidEntityType($entityType) || $entityId <= 0 || $editorUserId <= 0 || !in_array($action, ['grant', 'revoke'], true)) {
    jsonResponse(['ok' => false, 'error' => 'Нужны корректные entity_type, entity_id, editor_user_id и action'], 400);
}

try {
    $pdo = dbMysql();
    $actorUser = authCurrentUser();
    $actorUserId = (int)($actorUser['id'] ?? 0);

    $editor = userFindById($pdo, $editorUserId);
    if ($editor === null || (string)($editor['role_code'] ?? '') !== 'editor' || (int)($editor['is_active'] ?? 0) !== 1) {
        jsonResponse(['ok' => false, 'error' => 'Пользователь-редактор не найден или неактивен'], 400);
    }

    $exists = $entityType === 'template' ? (templateGet($pdo, $entityId) !== null) : (contentGet($pdo, $entityId) !== null);
    if (!$exists) {
        jsonResponse(['ok' => false, 'error' => 'Сущность не найдена'], 404);
    }

    if ($action === 'grant') {
        entityPermissionGrant($pdo, $entityType, $entityId, $editorUserId, ENTITY_PERMISSION_SCOPE_DIRECT, null, null);
        if ($entityType === 'template' && $includeLinkedContent) {
            entityPermissionGrantTemplateLinkedContent($pdo, $entityId, $editorUserId, null);
        }
    } else {
        entityPermissionRevoke($pdo, $entityType, $entityId, $editorUserId, ENTITY_PERMISSION_SCOPE_DIRECT, null);
        if ($entityType === 'template' && $includeLinkedContent) {
            entityPermissionRevokeTemplateLinkedContent($pdo, $entityId, $editorUserId);
        }
    }

    $logAction = $action === 'grant' ? 'entity_permission_grant' : 'entity_permission_revoke';
    $logDescription = ($action === 'grant' ? 'Выдача прав' : 'Отзыв прав') . ' [' . $entityType . ']' . ($includeLinkedContent ? ' + linked_content' : '');
    activityLogCreate($pdo, $actorUserId, $logAction, $logDescription, $entityType, $entityId);

    jsonResponse(['ok' => true, 'data' => ['entity_type' => $entityType, 'entity_id' => $entityId, 'editor_user_id' => $editorUserId, 'action' => $action, 'include_linked_content' => $includeLinkedContent]]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => '?? ??????? ????????? ????? ???????: ' . $e->getMessage()], 500);
}
