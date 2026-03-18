<?php
declare(strict_types=1);

require_once __DIR__ . '/user_repository.php';

function contentEnsureOwnershipSchema(PDO $pdo): void
{
    userEnsureSchema($pdo);

    $columns = [];
    $stmt = $pdo->query("SHOW COLUMNS FROM content_items");
    if ($stmt) {
        foreach ($stmt->fetchAll() as $row) {
            $columns[] = (string)($row['Field'] ?? '');
        }
    }

    if (!in_array('created_by', $columns, true)) {
        $pdo->exec("ALTER TABLE content_items ADD COLUMN created_by BIGINT(20) UNSIGNED DEFAULT NULL AFTER publish_to");
        $pdo->exec("ALTER TABLE content_items ADD KEY fk_content_created_by (created_by)");
        $pdo->exec("ALTER TABLE content_items ADD CONSTRAINT fk_content_created_by FOREIGN KEY (created_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE");
    }
    if (!in_array('updated_by', $columns, true)) {
        $pdo->exec("ALTER TABLE content_items ADD COLUMN updated_by BIGINT(20) UNSIGNED DEFAULT NULL AFTER created_by");
        $pdo->exec("ALTER TABLE content_items ADD KEY fk_content_updated_by (updated_by)");
        $pdo->exec("ALTER TABLE content_items ADD CONSTRAINT fk_content_updated_by FOREIGN KEY (updated_by) REFERENCES users (id) ON DELETE SET NULL ON UPDATE CASCADE");
    }

    $adminUserId = userFindFirstAdministratorId($pdo);
    if ($adminUserId !== null && $adminUserId > 0) {
        $stmt = $pdo->prepare("UPDATE content_items SET created_by = :admin_id WHERE created_by IS NULL");
        $stmt->execute([':admin_id' => $adminUserId]);
        $stmt = $pdo->prepare("UPDATE content_items SET updated_by = created_by WHERE updated_by IS NULL");
        $stmt->execute();
    }
}

function contentList(PDO $pdo, ?string $type = null, ?int $isActive = null): array
{
    contentEnsureOwnershipSchema($pdo);

    $sql = "SELECT c.id, c.type, c.title, c.body, c.media_url, c.data_json, c.is_active, c.publish_from, c.publish_to, c.created_by, c.updated_by, c.updated_at,
                   cu.full_name AS creator_name, cu.login AS creator_login
            FROM content_items c
            LEFT JOIN users cu ON cu.id = c.created_by
            WHERE 1=1";
    $params = [];

    if ($type !== null && $type !== '') {
        $sql .= " AND c.type = :type";
        $params['type'] = $type;
    }

    if ($isActive !== null) {
        $sql .= " AND c.is_active = :is_active";
        $params['is_active'] = $isActive ? 1 : 0;
    }

    $sql .= " ORDER BY c.updated_at DESC, c.id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function contentGet(PDO $pdo, int $id): ?array
{
    contentEnsureOwnershipSchema($pdo);
    $stmt = $pdo->prepare("
        SELECT c.id, c.type, c.title, c.body, c.media_url, c.data_json, c.is_active, c.publish_from, c.publish_to, c.created_by, c.updated_by, c.updated_at,
               cu.full_name AS creator_name, cu.login AS creator_login
        FROM content_items c
        LEFT JOIN users cu ON cu.id = c.created_by
        WHERE c.id = :id
        LIMIT 1
    ");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function contentCreate(PDO $pdo, array $payload): int
{
    contentEnsureOwnershipSchema($pdo);
    $payload['created_by'] = isset($payload['created_by']) ? (int)$payload['created_by'] : null;
    $payload['updated_by'] = isset($payload['updated_by']) ? (int)$payload['updated_by'] : $payload['created_by'];
    $stmt = $pdo->prepare(
        "INSERT INTO content_items (type, title, body, data_json, media_url, is_active, publish_from, publish_to, created_by, updated_by, created_at, updated_at)
         VALUES (:type, :title, :body, :data_json, :media_url, :is_active, :publish_from, :publish_to, :created_by, :updated_by, NOW(), NOW())"
    );
    $stmt->execute($payload);
    return (int)$pdo->lastInsertId();
}

function contentUpdate(PDO $pdo, int $id, array $payload): bool
{
    contentEnsureOwnershipSchema($pdo);
    $payload['updated_by'] = isset($payload['updated_by']) ? (int)$payload['updated_by'] : null;
    $payload['id'] = $id;
    $stmt = $pdo->prepare(
        "UPDATE content_items
         SET type=:type, title=:title, body=:body, data_json=:data_json, media_url=:media_url, is_active=:is_active,
             publish_from=:publish_from, publish_to=:publish_to, updated_by=:updated_by, updated_at=NOW()
         WHERE id=:id"
    );
    $stmt->execute($payload);
    return $stmt->rowCount() > 0;
}

function contentDelete(PDO $pdo, int $id): bool
{
    contentEnsureOwnershipSchema($pdo);
    $stmt = $pdo->prepare("DELETE FROM content_items WHERE id = :id");
    $stmt->execute(['id' => $id]);
    return $stmt->rowCount() > 0;
}

function contentFindManyByIds(PDO $pdo, array $ids): array
{
    contentEnsureOwnershipSchema($pdo);
    $ids = array_values(array_unique(array_map('intval', $ids)));
    $ids = array_filter($ids, static fn($v) => $v > 0);

    if (count($ids) === 0) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, type, title, body, media_url, data_json, updated_at, created_by, updated_by FROM content_items WHERE id IN ($placeholders)");
    $stmt->execute($ids);

    $rows = $stmt->fetchAll();
    $map = [];
    foreach ($rows as $row) {
        $map[(int)$row['id']] = $row;
    }

    return $map;
}
