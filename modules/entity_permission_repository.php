<?php
declare(strict_types=1);

require_once __DIR__ . '/user_repository.php';
require_once __DIR__ . '/template_repository.php';
require_once __DIR__ . '/content_repository.php';

const ENTITY_PERMISSION_SCOPE_DIRECT = 'direct';
const ENTITY_PERMISSION_SCOPE_TEMPLATE_LINK = 'template_link';

function entityPermissionEnsureSchema(PDO $pdo): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    userEnsureSchema($pdo);
    templateEnsureOwnershipSchema($pdo);
    contentEnsureOwnershipSchema($pdo);

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS entity_permissions (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            entity_type enum('template','content') NOT NULL,
            entity_id bigint(20) unsigned NOT NULL,
            user_id bigint(20) unsigned NOT NULL,
            scope enum('direct','template_link') NOT NULL DEFAULT 'direct',
            source_template_id bigint(20) unsigned DEFAULT NULL,
            granted_by bigint(20) unsigned DEFAULT NULL,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY uq_entity_permission (entity_type, entity_id, user_id, scope, source_template_id),
            KEY idx_entity_permission_lookup (entity_type, entity_id, user_id),
            KEY idx_entity_permission_user (user_id),
            KEY idx_entity_permission_source_template (source_template_id),
            CONSTRAINT fk_entity_permission_user FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_entity_permission_granted_by FOREIGN KEY (granted_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $ready = true;
}

function entityPermissionIsValidEntityType(string $entityType): bool
{
    return in_array($entityType, ['template', 'content'], true);
}

function entityPermissionIsValidScope(string $scope): bool
{
    return in_array($scope, [ENTITY_PERMISSION_SCOPE_DIRECT, ENTITY_PERMISSION_SCOPE_TEMPLATE_LINK], true);
}

function entityPermissionGrant(PDO $pdo, string $entityType, int $entityId, int $userId, string $scope = ENTITY_PERMISSION_SCOPE_DIRECT, ?int $sourceTemplateId = null, ?int $grantedBy = null): void
{
    entityPermissionEnsureSchema($pdo);
    if (!entityPermissionIsValidEntityType($entityType) || $entityId <= 0 || $userId <= 0 || !entityPermissionIsValidScope($scope)) {
        return;
    }
    if ($scope !== ENTITY_PERMISSION_SCOPE_TEMPLATE_LINK) {
        $sourceTemplateId = null;
    }
    if ($scope === ENTITY_PERMISSION_SCOPE_TEMPLATE_LINK && ($sourceTemplateId === null || $sourceTemplateId <= 0)) {
        return;
    }

    $stmt = $pdo->prepare("
        INSERT INTO entity_permissions (entity_type, entity_id, user_id, scope, source_template_id, granted_by, created_at, updated_at)
        VALUES (:entity_type, :entity_id, :user_id, :scope, :source_template_id, :granted_by, NOW(), NOW())
        ON DUPLICATE KEY UPDATE granted_by = VALUES(granted_by), updated_at = NOW()
    ");
    $stmt->execute([
        ':entity_type' => $entityType,
        ':entity_id' => $entityId,
        ':user_id' => $userId,
        ':scope' => $scope,
        ':source_template_id' => $sourceTemplateId,
        ':granted_by' => $grantedBy,
    ]);
}

function entityPermissionRevoke(PDO $pdo, string $entityType, int $entityId, int $userId, string $scope = ENTITY_PERMISSION_SCOPE_DIRECT, ?int $sourceTemplateId = null): void
{
    entityPermissionEnsureSchema($pdo);
    if (!entityPermissionIsValidEntityType($entityType) || $entityId <= 0 || $userId <= 0 || !entityPermissionIsValidScope($scope)) {
        return;
    }
    if ($scope !== ENTITY_PERMISSION_SCOPE_TEMPLATE_LINK) {
        $sourceTemplateId = null;
    }
    if ($scope === ENTITY_PERMISSION_SCOPE_TEMPLATE_LINK && ($sourceTemplateId === null || $sourceTemplateId <= 0)) {
        return;
    }

    $stmt = $pdo->prepare("
        DELETE FROM entity_permissions
        WHERE entity_type = :entity_type
          AND entity_id = :entity_id
          AND user_id = :user_id
          AND scope = :scope
          AND ((:source_template_id_is_null IS NULL AND source_template_id IS NULL) OR source_template_id = :source_template_id_value)
    ");
    $stmt->execute([
        ':entity_type' => $entityType,
        ':entity_id' => $entityId,
        ':user_id' => $userId,
        ':scope' => $scope,
        ':source_template_id_is_null' => $sourceTemplateId,
        ':source_template_id_value' => $sourceTemplateId,
    ]);
}

function entityPermissionHasAny(PDO $pdo, string $entityType, int $entityId, int $userId): bool
{
    entityPermissionEnsureSchema($pdo);
    if (!entityPermissionIsValidEntityType($entityType) || $entityId <= 0 || $userId <= 0) {
        return false;
    }
    $stmt = $pdo->prepare("SELECT id FROM entity_permissions WHERE entity_type = :entity_type AND entity_id = :entity_id AND user_id = :user_id LIMIT 1");
    $stmt->execute([
        ':entity_type' => $entityType,
        ':entity_id' => $entityId,
        ':user_id' => $userId,
    ]);
    return (bool)$stmt->fetchColumn();
}

function entityPermissionListForEntity(PDO $pdo, string $entityType, int $entityId): array
{
    entityPermissionEnsureSchema($pdo);
    if (!entityPermissionIsValidEntityType($entityType) || $entityId <= 0) {
        return [];
    }
    $stmt = $pdo->prepare("
        SELECT ep.user_id,
               MAX(CASE WHEN ep.scope = 'direct' THEN 1 ELSE 0 END) AS has_direct,
               MAX(CASE WHEN ep.scope = 'template_link' THEN 1 ELSE 0 END) AS has_linked
        FROM entity_permissions ep
        WHERE ep.entity_type = :entity_type
          AND ep.entity_id = :entity_id
        GROUP BY ep.user_id
    ");
    $stmt->execute([
        ':entity_type' => $entityType,
        ':entity_id' => $entityId,
    ]);
    return $stmt->fetchAll();
}

function entityPermissionGetTemplateLinkedContentIds(PDO $pdo, int $templateId): array
{
    templateEnsureOwnershipSchema($pdo);
    if ($templateId <= 0) {
        return [];
    }
    $stmt = $pdo->prepare("
        SELECT DISTINCT content_id
        FROM template_blocks
        WHERE template_id = :template_id
          AND content_mode = 'fixed'
          AND content_id IS NOT NULL
          AND content_id > 0
    ");
    $stmt->execute([':template_id' => $templateId]);
    $ids = $stmt->fetchAll(PDO::FETCH_COLUMN);
    return array_values(array_unique(array_filter(array_map('intval', is_array($ids) ? $ids : []), static fn(int $v): bool => $v > 0)));
}

function entityPermissionGrantTemplateLinkedContent(PDO $pdo, int $templateId, int $userId, ?int $grantedBy = null): void
{
    $contentIds = entityPermissionGetTemplateLinkedContentIds($pdo, $templateId);
    foreach ($contentIds as $contentId) {
        entityPermissionGrant($pdo, 'content', $contentId, $userId, ENTITY_PERMISSION_SCOPE_TEMPLATE_LINK, $templateId, $grantedBy);
    }
}

function entityPermissionRevokeTemplateLinkedContent(PDO $pdo, int $templateId, int $userId): void
{
    entityPermissionEnsureSchema($pdo);
    if ($templateId <= 0 || $userId <= 0) {
        return;
    }
    $stmt = $pdo->prepare("
        DELETE FROM entity_permissions
        WHERE entity_type = 'content'
          AND user_id = :user_id
          AND scope = 'template_link'
          AND source_template_id = :source_template_id
    ");
    $stmt->execute([
        ':user_id' => $userId,
        ':source_template_id' => $templateId,
    ]);
}

function entityPermissionRevokeAllForEntity(PDO $pdo, string $entityType, int $entityId): void
{
    entityPermissionEnsureSchema($pdo);
    if (!entityPermissionIsValidEntityType($entityType) || $entityId <= 0) {
        return;
    }
    $stmt = $pdo->prepare("DELETE FROM entity_permissions WHERE entity_type = :entity_type AND entity_id = :entity_id");
    $stmt->execute([
        ':entity_type' => $entityType,
        ':entity_id' => $entityId,
    ]);
}
