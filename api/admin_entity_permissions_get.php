<?php
declare(strict_types=1);

require_once __DIR__ . '/../core/helpers.php';
require_once __DIR__ . '/../core/auth.php';
require_once __DIR__ . '/../core/db_mysql.php';
require_once __DIR__ . '/../modules/user_repository.php';
require_once __DIR__ . '/../modules/template_repository.php';
require_once __DIR__ . '/../modules/content_repository.php';
require_once __DIR__ . '/../modules/entity_permission_repository.php';

requireAdministratorApiAuth();

$entityType = trim((string)($_GET['entity_type'] ?? ''));
$entityId = isset($_GET['entity_id']) ? (int)$_GET['entity_id'] : 0;

if (!entityPermissionIsValidEntityType($entityType) || $entityId <= 0) {
    jsonResponse(['ok' => false, 'error' => 'Нужны корректные entity_type и entity_id'], 400);
}

try {
    $pdo = dbMysql();
    $exists = $entityType === 'template' ? (templateGet($pdo, $entityId) !== null) : (contentGet($pdo, $entityId) !== null);
    if (!$exists) {
        jsonResponse(['ok' => false, 'error' => 'Сущность не найдена'], 404);
    }

    $users = userListAllWithRole($pdo);
    $editors = array_values(array_filter($users, static function (array $user): bool {
        return (string)($user['role_code'] ?? '') === 'editor' && (int)($user['is_active'] ?? 0) === 1;
    }));

    $grants = entityPermissionListForEntity($pdo, $entityType, $entityId);
    $grantMap = [];
    foreach ($grants as $row) {
        $grantMap[(int)($row['user_id'] ?? 0)] = [
            'has_direct' => (int)($row['has_direct'] ?? 0) === 1,
            'has_linked' => (int)($row['has_linked'] ?? 0) === 1,
        ];
    }

    $result = [];
    foreach ($editors as $editor) {
        $editorId = (int)($editor['id'] ?? 0);
        $perm = $grantMap[$editorId] ?? ['has_direct' => false, 'has_linked' => false];
        $result[] = [
            'user_id' => $editorId,
            'full_name' => (string)($editor['full_name'] ?? ''),
            'login' => (string)($editor['login'] ?? ''),
            'has_access' => ($perm['has_direct'] || $perm['has_linked']),
            'has_direct' => $perm['has_direct'],
            'has_linked' => $perm['has_linked'],
        ];
    }

    jsonResponse(['ok' => true, 'data' => ['entity_type' => $entityType, 'entity_id' => $entityId, 'editors' => $result]]);
} catch (Throwable $e) {
    jsonResponse(['ok' => false, 'error' => 'Не удалось получить список прав'], 500);
}

