<?php
declare(strict_types=1);

function contentList(PDO $pdo, ?string $type = null, ?int $isActive = null): array
{
    $sql = "SELECT id, type, title, body, media_url, data_json, is_active, publish_from, publish_to, updated_at
            FROM content_items
            WHERE 1=1";
    $params = [];

    if ($type !== null && $type !== '') {
        $sql .= " AND type = :type";
        $params['type'] = $type;
    }

    if ($isActive !== null) {
        $sql .= " AND is_active = :is_active";
        $params['is_active'] = $isActive ? 1 : 0;
    }

    $sql .= " ORDER BY updated_at DESC, id DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    return $stmt->fetchAll();
}

function contentGet(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT id, type, title, body, media_url, data_json, is_active, publish_from, publish_to, updated_at FROM content_items WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function contentCreate(PDO $pdo, array $payload): int
{
    $stmt = $pdo->prepare(
        "INSERT INTO content_items (type, title, body, data_json, media_url, is_active, publish_from, publish_to, created_at, updated_at)
         VALUES (:type, :title, :body, :data_json, :media_url, :is_active, :publish_from, :publish_to, NOW(), NOW())"
    );
    $stmt->execute($payload);
    return (int)$pdo->lastInsertId();
}

function contentUpdate(PDO $pdo, int $id, array $payload): bool
{
    $payload['id'] = $id;
    $stmt = $pdo->prepare(
        "UPDATE content_items
         SET type=:type, title=:title, body=:body, data_json=:data_json, media_url=:media_url, is_active=:is_active,
             publish_from=:publish_from, publish_to=:publish_to, updated_at=NOW()
         WHERE id=:id"
    );
    $stmt->execute($payload);
    return $stmt->rowCount() > 0;
}

function contentDelete(PDO $pdo, int $id): bool
{
    $stmt = $pdo->prepare("DELETE FROM content_items WHERE id = :id");
    $stmt->execute(['id' => $id]);
    return $stmt->rowCount() > 0;
}

function contentFindManyByIds(PDO $pdo, array $ids): array
{
    $ids = array_values(array_unique(array_map('intval', $ids)));
    $ids = array_filter($ids, static fn($v) => $v > 0);

    if (count($ids) === 0) {
        return [];
    }

    $placeholders = implode(',', array_fill(0, count($ids), '?'));
    $stmt = $pdo->prepare("SELECT id, type, title, body, media_url, data_json FROM content_items WHERE id IN ($placeholders)");
    $stmt->execute($ids);

    $rows = $stmt->fetchAll();
    $map = [];
    foreach ($rows as $row) {
        $map[(int)$row['id']] = $row;
    }

    return $map;
}
