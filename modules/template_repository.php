<?php
declare(strict_types=1);

function ensureTemplateStatusEnum(PDO $pdo): void
{
    $stmt = $pdo->query("SHOW COLUMNS FROM templates LIKE 'status'");
    if (!$stmt) {
        return;
    }

    $row = $stmt->fetch();
    if (!is_array($row)) {
        return;
    }

    $typeRaw = (string)($row['Type'] ?? '');
    if (!preg_match('/^enum\((.*)\)$/i', $typeRaw, $m)) {
        return;
    }

    preg_match_all("/'([^']+)'/", $m[1], $matches);
    $values = $matches[1] ?? [];
    $required = ['draft', 'work', 'archive'];
    if ($values === $required) {
        return;
    }

    $needsTransitionalAlter = !in_array('work', $values, true) || !in_array('archive', $values, true);
    if ($needsTransitionalAlter) {
        $transitional = array_values(array_unique(array_merge($values, $required, ['active', 'archived'])));
        $enumSql = implode(',', array_map(static fn(string $v): string => "'" . str_replace("'", "''", $v) . "'", $transitional));
        $pdo->exec("ALTER TABLE templates MODIFY COLUMN status ENUM($enumSql) NOT NULL DEFAULT 'draft'");
    }

    $pdo->exec("UPDATE templates SET status='work' WHERE status='active'");
    $pdo->exec("UPDATE templates SET status='archive' WHERE status='archived'");

    $enumSql = implode(',', array_map(static fn(string $v): string => "'" . str_replace("'", "''", $v) . "'", $required));
    $pdo->exec("ALTER TABLE templates MODIFY COLUMN status ENUM($enumSql) NOT NULL DEFAULT 'draft'");
}

function templateCreate(PDO $pdo, string $name, string $description, string $layoutJson, string $status): int
{
    $sql = "INSERT INTO templates (name, description, layout_json, status, version, created_at, updated_at) VALUES (:name, :description, :layout_json, :status, 1, NOW(), NOW())";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'name' => $name,
        'description' => $description,
        'layout_json' => $layoutJson,
        'status' => $status,
    ]);

    return (int)$pdo->lastInsertId();
}

function templateUpdate(PDO $pdo, int $id, string $name, string $description, string $layoutJson, string $status): bool
{
    $sql = "UPDATE templates SET name=:name, description=:description, layout_json=:layout_json, status=:status, version=version+1, updated_at=NOW() WHERE id=:id";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        'id' => $id,
        'name' => $name,
        'description' => $description,
        'layout_json' => $layoutJson,
        'status' => $status,
    ]);

    return $stmt->rowCount() > 0;
}

function templateList(PDO $pdo): array
{
    return $pdo->query("SELECT id, name, description, status, version, updated_at FROM templates ORDER BY updated_at DESC, id DESC")->fetchAll();
}

function templateGet(PDO $pdo, int $id): ?array
{
    $stmt = $pdo->prepare("SELECT id, name, description, status, version, layout_json, updated_at FROM templates WHERE id = :id LIMIT 1");
    $stmt->execute(['id' => $id]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function templateGetByStatus(PDO $pdo, string $status): ?array
{
    $stmt = $pdo->prepare("SELECT id, name, description, status, version, layout_json, updated_at FROM templates WHERE status = :status ORDER BY updated_at DESC, id DESC LIMIT 1");
    $stmt->execute(['status' => $status]);
    $row = $stmt->fetch();

    return $row ?: null;
}

function templateDeleteBlocks(PDO $pdo, int $templateId): void
{
    $stmt = $pdo->prepare("DELETE FROM template_blocks WHERE template_id = :template_id");
    $stmt->execute(['template_id' => $templateId]);
}

function templateInsertBlock(PDO $pdo, int $templateId, array $block, int $sortOrder): void
{
    $stmt = $pdo->prepare("INSERT INTO template_blocks (template_id, block_key, x_pct, y_pct, w_pct, h_pct, z_index, content_mode, content_id, content_type, style_json, sort_order, created_at, updated_at) VALUES (:template_id, :block_key, :x_pct, :y_pct, :w_pct, :h_pct, :z_index, :content_mode, :content_id, :content_type, :style_json, :sort_order, NOW(), NOW())");
    $stmt->execute([
        'template_id' => $templateId,
        'block_key' => $block['block_key'],
        'x_pct' => $block['x_pct'],
        'y_pct' => $block['y_pct'],
        'w_pct' => $block['w_pct'],
        'h_pct' => $block['h_pct'],
        'z_index' => $block['z_index'],
        'content_mode' => $block['content_mode'],
        'content_id' => $block['content_id'],
        'content_type' => $block['content_type'],
        'style_json' => $block['style_json'],
        'sort_order' => $sortOrder,
    ]);
}

function templateGetBlocks(PDO $pdo, int $templateId): array
{
    $stmt = $pdo->prepare("SELECT id, template_id, block_key, x_pct, y_pct, w_pct, h_pct, z_index, content_mode, content_id, content_type, style_json, sort_order FROM template_blocks WHERE template_id = :template_id ORDER BY sort_order ASC, id ASC");
    $stmt->execute(['template_id' => $templateId]);
    return $stmt->fetchAll();
}

function templateActivate(PDO $pdo, int $templateId): bool
{
    if (templateGet($pdo, $templateId) === null) {
        return false;
    }

    $pdo->exec("UPDATE templates SET status='draft' WHERE status='work'");
    $stmt = $pdo->prepare("UPDATE templates SET status='work', updated_at=NOW() WHERE id = :id");
    $stmt->execute(['id' => $templateId]);
    return true;
}

function templateDelete(PDO $pdo, int $templateId): bool
{
    $stmt = $pdo->prepare("DELETE FROM templates WHERE id = :id");
    $stmt->execute(['id' => $templateId]);

    return $stmt->rowCount() > 0;
}

function templateUsageStats(PDO $pdo, int $templateId): array
{
    $stats = [
        'schedule_rules_total' => 0,
        'schedule_rules_active' => 0,
        'screen_commands_total' => 0,
        'screen_commands_active' => 0,
    ];

    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM schedule_rules WHERE template_id = :template_id");
    $stmt->execute(['template_id' => $templateId]);
    $stats['schedule_rules_total'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM schedule_rules WHERE template_id = :template_id AND is_active = 1");
    $stmt->execute(['template_id' => $templateId]);
    $stats['schedule_rules_active'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM screen_commands WHERE template_id = :template_id");
    $stmt->execute(['template_id' => $templateId]);
    $stats['screen_commands_total'] = (int)$stmt->fetchColumn();

    $stmt = $pdo->prepare("SELECT COUNT(*) AS cnt FROM screen_commands WHERE template_id = :template_id AND is_active = 1");
    $stmt->execute(['template_id' => $templateId]);
    $stats['screen_commands_active'] = (int)$stmt->fetchColumn();

    return $stats;
}

function templateDeleteScheduleRules(PDO $pdo, int $templateId): void
{
    $stmt = $pdo->prepare("DELETE FROM schedule_rules WHERE template_id = :template_id");
    $stmt->execute(['template_id' => $templateId]);
}
