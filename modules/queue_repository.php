<?php
declare(strict_types=1);

function queueAllowedTypes(): array
{
    return ['active', 'test', 'archive'];
}

function queueNormalizeType(string $value): string
{
    $type = trim(strtolower($value));
    return in_array($type, queueAllowedTypes(), true) ? $type : 'archive';
}

function queueTypeLabel(string $value): string
{
    $type = queueNormalizeType($value);
    if ($type === 'active') {
        return 'активная';
    }
    if ($type === 'test') {
        return 'тестовая';
    }
    return 'архив';
}

function queueEnsureSchema(PDO $pdo): void
{
    static $ready = false;
    if ($ready) {
        return;
    }

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS display_queues (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            name varchar(150) NOT NULL,
            is_active tinyint(1) NOT NULL DEFAULT 0,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_display_queues_active (is_active)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $pdo->exec("
        CREATE TABLE IF NOT EXISTS display_queue_items (
            id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
            queue_id bigint(20) unsigned NOT NULL,
            template_id bigint(20) unsigned NOT NULL,
            duration_sec int(10) unsigned NOT NULL DEFAULT 15,
            sort_order int(10) unsigned NOT NULL DEFAULT 100,
            created_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY idx_display_queue_items_queue_sort (queue_id, sort_order, id),
            KEY idx_display_queue_items_template (template_id),
            CONSTRAINT fk_display_queue_items_queue FOREIGN KEY (queue_id) REFERENCES display_queues (id) ON DELETE CASCADE ON UPDATE CASCADE,
            CONSTRAINT fk_display_queue_items_template FOREIGN KEY (template_id) REFERENCES templates (id) ON DELETE CASCADE ON UPDATE CASCADE
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4
    ");

    $columnStmt = $pdo->query("SHOW COLUMNS FROM display_queues LIKE 'queue_type'");
    if ($columnStmt && !$columnStmt->fetch()) {
        $pdo->exec("ALTER TABLE display_queues ADD COLUMN queue_type varchar(16) NOT NULL DEFAULT 'archive' AFTER name");
        $pdo->exec("ALTER TABLE display_queues ADD KEY idx_display_queues_type (queue_type)");
    }

    $count = (int)$pdo->query('SELECT COUNT(*) FROM display_queues')->fetchColumn();
    if ($count <= 0) {
        $stmt = $pdo->prepare("
            INSERT INTO display_queues (name, queue_type, is_active, created_at, updated_at)
            VALUES (:name, 'active', 1, NOW(), NOW())
        ");
        $stmt->execute([':name' => 'Основная очередь']);
    }

    $pdo->exec("
        UPDATE display_queues
        SET queue_type = CASE
            WHEN queue_type IN ('active', 'test', 'archive') THEN queue_type
            WHEN is_active = 1 THEN 'active'
            ELSE 'archive'
        END
    ");

    $activeRows = $pdo->query("SELECT id FROM display_queues WHERE queue_type = 'active' ORDER BY updated_at DESC, id DESC")->fetchAll(PDO::FETCH_COLUMN);
    if (count($activeRows) > 1) {
        $keepId = (int)$activeRows[0];
        $stmt = $pdo->prepare("UPDATE display_queues SET queue_type = 'archive' WHERE queue_type = 'active' AND id <> :id");
        $stmt->execute([':id' => $keepId]);
    }

    $testRows = $pdo->query("SELECT id FROM display_queues WHERE queue_type = 'test' ORDER BY updated_at DESC, id DESC")->fetchAll(PDO::FETCH_COLUMN);
    if (count($testRows) > 1) {
        $keepId = (int)$testRows[0];
        $stmt = $pdo->prepare("UPDATE display_queues SET queue_type = 'archive' WHERE queue_type = 'test' AND id <> :id");
        $stmt->execute([':id' => $keepId]);
    }

    $activeCount = (int)$pdo->query("SELECT COUNT(*) FROM display_queues WHERE queue_type = 'active'")->fetchColumn();
    if ($activeCount <= 0) {
        $pdo->exec("
            UPDATE display_queues
            SET queue_type = 'active'
            WHERE id = (SELECT q.id FROM (SELECT id FROM display_queues ORDER BY id ASC LIMIT 1) q)
        ");
    }

    $pdo->exec("UPDATE display_queues SET is_active = CASE WHEN queue_type = 'active' THEN 1 ELSE 0 END");

    $ready = true;
}

function queueListAll(PDO $pdo): array
{
    queueEnsureSchema($pdo);
    $stmt = $pdo->query("
        SELECT q.id,
               q.name,
               q.queue_type,
               CASE WHEN q.queue_type = 'active' THEN 1 ELSE 0 END AS is_active,
               q.created_at,
               q.updated_at,
               (SELECT COUNT(*) FROM display_queue_items qi WHERE qi.queue_id = q.id) AS items_count
        FROM display_queues q
        ORDER BY
            CASE q.queue_type
                WHEN 'active' THEN 0
                WHEN 'test' THEN 1
                ELSE 2
            END ASC,
            q.updated_at DESC,
            q.id DESC
    ");
    return $stmt->fetchAll();
}

function queueCreate(PDO $pdo, string $name): int
{
    queueEnsureSchema($pdo);
    $stmt = $pdo->prepare("
        INSERT INTO display_queues (name, queue_type, is_active, created_at, updated_at)
        VALUES (:name, 'archive', 0, NOW(), NOW())
    ");
    $stmt->execute([':name' => $name]);
    return (int)$pdo->lastInsertId();
}

function queueGetByType(PDO $pdo, string $queueType): ?array
{
    queueEnsureSchema($pdo);
    $type = queueNormalizeType($queueType);
    $stmt = $pdo->prepare("
        SELECT id, name, queue_type, CASE WHEN queue_type = 'active' THEN 1 ELSE 0 END AS is_active, created_at, updated_at
        FROM display_queues
        WHERE queue_type = :queue_type
        ORDER BY updated_at DESC, id DESC
        LIMIT 1
    ");
    $stmt->execute([':queue_type' => $type]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function queueGetActive(PDO $pdo): ?array
{
    return queueGetByType($pdo, 'active');
}

function queueGetDefault(PDO $pdo): ?array
{
    queueEnsureSchema($pdo);
    $active = queueGetActive($pdo);
    if ($active !== null) {
        return $active;
    }

    $stmt = $pdo->query("
        SELECT id, name, queue_type, CASE WHEN queue_type = 'active' THEN 1 ELSE 0 END AS is_active, created_at, updated_at
        FROM display_queues
        ORDER BY updated_at DESC, id DESC
        LIMIT 1
    ");
    $row = $stmt->fetch();
    return $row ?: null;
}

function queueGetById(PDO $pdo, int $queueId): ?array
{
    queueEnsureSchema($pdo);
    $stmt = $pdo->prepare("
        SELECT id, name, queue_type, CASE WHEN queue_type = 'active' THEN 1 ELSE 0 END AS is_active, created_at, updated_at
        FROM display_queues
        WHERE id = :id
        LIMIT 1
    ");
    $stmt->execute([':id' => $queueId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function queueGetOrActive(PDO $pdo, int $queueId): ?array
{
    if ($queueId > 0) {
        return queueGetById($pdo, $queueId);
    }
    return queueGetDefault($pdo);
}

function queueGetItems(PDO $pdo, int $queueId): array
{
    queueEnsureSchema($pdo);
    $stmt = $pdo->prepare("
        SELECT qi.id, qi.queue_id, qi.template_id, qi.duration_sec, qi.sort_order,
               t.name AS template_name, t.status AS template_status, t.updated_at AS template_updated_at
        FROM display_queue_items qi
        INNER JOIN templates t ON t.id = qi.template_id
        WHERE qi.queue_id = :queue_id
        ORDER BY qi.sort_order ASC, qi.id ASC
    ");
    $stmt->execute([':queue_id' => $queueId]);
    return $stmt->fetchAll();
}

function queueActivate(PDO $pdo, int $queueId): void
{
    queueEnsureSchema($pdo);
    queueUpdateMeta($pdo, $queueId, (string)(queueGetById($pdo, $queueId)['name'] ?? ''), 'active');
}

function queueUpdateMeta(PDO $pdo, int $queueId, string $name, string $queueType): void
{
    queueEnsureSchema($pdo);
    $name = trim($name);
    if ($name === '') {
        $name = queueGenerateName($pdo);
    }
    $type = queueNormalizeType($queueType);
    $isActive = $type === 'active' ? 1 : 0;

    $pdo->beginTransaction();
    try {
        if ($type === 'active') {
            $pdo->exec("UPDATE display_queues SET queue_type = 'archive' WHERE queue_type = 'active'");
        } elseif ($type === 'test') {
            $pdo->exec("UPDATE display_queues SET queue_type = 'archive' WHERE queue_type = 'test'");
        }

        $stmt = $pdo->prepare("
            UPDATE display_queues
            SET name = :name,
                queue_type = :queue_type,
                is_active = :is_active,
                updated_at = NOW()
            WHERE id = :id
        ");
        $stmt->execute([
            ':id' => $queueId,
            ':name' => $name,
            ':queue_type' => $type,
            ':is_active' => $isActive,
        ]);

        $pdo->exec("UPDATE display_queues SET is_active = CASE WHEN queue_type = 'active' THEN 1 ELSE 0 END");
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function queueSaveItems(PDO $pdo, int $queueId, array $items): void
{
    queueEnsureSchema($pdo);
    $pdo->beginTransaction();
    try {
        $delete = $pdo->prepare("DELETE FROM display_queue_items WHERE queue_id = :queue_id");
        $delete->execute([':queue_id' => $queueId]);

        $insert = $pdo->prepare("
            INSERT INTO display_queue_items (queue_id, template_id, duration_sec, sort_order, created_at, updated_at)
            VALUES (:queue_id, :template_id, :duration_sec, :sort_order, NOW(), NOW())
        ");

        foreach ($items as $index => $item) {
            $insert->execute([
                ':queue_id' => $queueId,
                ':template_id' => (int)$item['template_id'],
                ':duration_sec' => max(1, (int)$item['duration_sec']),
                ':sort_order' => $index + 1,
            ]);
        }

        $touch = $pdo->prepare("UPDATE display_queues SET updated_at = NOW() WHERE id = :id");
        $touch->execute([':id' => $queueId]);
        $pdo->commit();
    } catch (Throwable $e) {
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        throw $e;
    }
}

function queueGenerateName(PDO $pdo): string
{
    queueEnsureSchema($pdo);
    $rows = queueListAll($pdo);
    $max = 0;
    foreach ($rows as $row) {
        $name = trim((string)($row['name'] ?? ''));
        if (preg_match('/^Очередь\s+(\d+)$/u', $name, $m)) {
            $max = max($max, (int)$m[1]);
        }
    }
    return 'Очередь ' . ($max + 1);
}
