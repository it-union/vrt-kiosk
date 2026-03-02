<?php
declare(strict_types=1);

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

    $count = (int)$pdo->query('SELECT COUNT(*) FROM display_queues')->fetchColumn();
    if ($count <= 0) {
        $stmt = $pdo->prepare("INSERT INTO display_queues (name, is_active, created_at, updated_at) VALUES (:name, 1, NOW(), NOW())");
        $stmt->execute([':name' => 'Основная очередь']);
    }

    $activeCount = (int)$pdo->query('SELECT COUNT(*) FROM display_queues WHERE is_active = 1')->fetchColumn();
    if ($activeCount <= 0) {
        $pdo->exec("UPDATE display_queues SET is_active = 1 WHERE id = (SELECT q.id FROM (SELECT id FROM display_queues ORDER BY id ASC LIMIT 1) q)");
    }

    $ready = true;
}

function queueListAll(PDO $pdo): array
{
    queueEnsureSchema($pdo);
    $stmt = $pdo->query("
        SELECT q.id, q.name, q.is_active, q.created_at, q.updated_at,
               (SELECT COUNT(*) FROM display_queue_items qi WHERE qi.queue_id = q.id) AS items_count
        FROM display_queues q
        ORDER BY q.is_active DESC, q.updated_at DESC, q.id DESC
    ");
    return $stmt->fetchAll();
}

function queueCreate(PDO $pdo, string $name): int
{
    queueEnsureSchema($pdo);
    $stmt = $pdo->prepare("INSERT INTO display_queues (name, is_active, created_at, updated_at) VALUES (:name, 0, NOW(), NOW())");
    $stmt->execute([':name' => $name]);
    return (int)$pdo->lastInsertId();
}

function queueGetActive(PDO $pdo): ?array
{
    queueEnsureSchema($pdo);
    $stmt = $pdo->query("SELECT id, name, is_active, created_at, updated_at FROM display_queues WHERE is_active = 1 ORDER BY id ASC LIMIT 1");
    $row = $stmt->fetch();
    return $row ?: null;
}

function queueGetById(PDO $pdo, int $queueId): ?array
{
    queueEnsureSchema($pdo);
    $stmt = $pdo->prepare("SELECT id, name, is_active, created_at, updated_at FROM display_queues WHERE id = :id LIMIT 1");
    $stmt->execute([':id' => $queueId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function queueGetOrActive(PDO $pdo, int $queueId): ?array
{
    if ($queueId > 0) {
        return queueGetById($pdo, $queueId);
    }
    return queueGetActive($pdo);
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
    $pdo->exec("UPDATE display_queues SET is_active = 0");
    $stmt = $pdo->prepare("UPDATE display_queues SET is_active = 1, updated_at = NOW() WHERE id = :id");
    $stmt->execute([':id' => $queueId]);
}

function queueUpdateMeta(PDO $pdo, int $queueId, string $name, bool $setActive): void
{
    queueEnsureSchema($pdo);
    $name = trim($name);
    if ($name === '') {
        $name = queueGenerateName($pdo);
    }

    $pdo->beginTransaction();
    try {
        if ($setActive) {
            $pdo->exec("UPDATE display_queues SET is_active = 0");
            $stmt = $pdo->prepare("
                UPDATE display_queues
                SET name = :name, is_active = 1, updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $queueId,
                ':name' => $name,
            ]);
        } else {
            $stmt = $pdo->prepare("
                UPDATE display_queues
                SET name = :name, updated_at = NOW()
                WHERE id = :id
            ");
            $stmt->execute([
                ':id' => $queueId,
                ':name' => $name,
            ]);
        }

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
