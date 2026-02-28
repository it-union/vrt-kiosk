<?php
declare(strict_types=1);

function screenFindActiveManual(PDO $pdo, int $screenId): ?array
{
    $sql = "SELECT sc.screen_id, sc.id AS command_id, sc.template_id, sc.content_id, sc.ends_at, ci.type, ci.title, ci.body, ci.media_url, ci.data_json FROM screen_commands sc LEFT JOIN content_items ci ON ci.id = sc.content_id WHERE sc.screen_id = :screen_id AND sc.is_active = 1 AND sc.command_type = 'show_content' AND sc.starts_at <= NOW() AND (sc.ends_at IS NULL OR sc.ends_at > NOW()) ORDER BY sc.starts_at DESC, sc.id DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['screen_id' => $screenId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function screenFindActiveSchedule(PDO $pdo, int $screenId): ?array
{
    $sql = "SELECT sr.id AS rule_id, sr.template_id, sr.content_id, ci.type, ci.title, ci.body, ci.media_url, ci.data_json FROM schedule_rules sr LEFT JOIN content_items ci ON ci.id = sr.content_id WHERE sr.is_active = 1 AND (sr.screen_id = :screen_id OR sr.screen_id IS NULL) AND (sr.date_from IS NULL OR sr.date_from <= CURDATE()) AND (sr.date_to IS NULL OR sr.date_to >= CURDATE()) AND (sr.time_from IS NULL OR sr.time_from <= CURTIME()) AND (sr.time_to IS NULL OR sr.time_to >= CURTIME()) AND (sr.days_mask & (1 << WEEKDAY(NOW()))) > 0 ORDER BY sr.priority DESC, sr.id DESC LIMIT 1";
    $stmt = $pdo->prepare($sql);
    $stmt->execute(['screen_id' => $screenId]);
    $row = $stmt->fetch();
    return $row ?: null;
}

function screenContentExists(PDO $pdo, int $contentId): bool
{
    $stmt = $pdo->prepare('SELECT id FROM content_items WHERE id = :id AND is_active = 1 LIMIT 1');
    $stmt->execute(['id' => $contentId]);
    return (bool)$stmt->fetch();
}

function screenDeactivateManualCommands(PDO $pdo, int $screenId): void
{
    $stmt = $pdo->prepare("UPDATE screen_commands SET is_active = 0 WHERE screen_id = :screen_id AND command_type = 'show_content' AND is_active = 1");
    $stmt->execute(['screen_id' => $screenId]);
}

function screenCreateManualCommand(PDO $pdo, int $screenId, int $contentId, int $durationMinutes): int
{
    $stmt = $pdo->prepare("INSERT INTO screen_commands (screen_id, command_type, content_id, starts_at, ends_at, is_active, created_at) VALUES (:screen_id, 'show_content', :content_id, NOW(), DATE_ADD(NOW(), INTERVAL :minutes MINUTE), 1, NOW())");
    $stmt->bindValue(':screen_id', $screenId, PDO::PARAM_INT);
    $stmt->bindValue(':content_id', $contentId, PDO::PARAM_INT);
    $stmt->bindValue(':minutes', $durationMinutes, PDO::PARAM_INT);
    $stmt->execute();
    return (int)$pdo->lastInsertId();
}

function screenUpsertManualState(PDO $pdo, int $screenId, int $commandId, int $contentId, int $durationMinutes): void
{
    $stmt = $pdo->prepare("INSERT INTO screen_state (screen_id, source, command_id, content_id, applied_at, expires_at, updated_at) VALUES (:screen_id, 'manual', :command_id, :content_id, NOW(), DATE_ADD(NOW(), INTERVAL :minutes MINUTE), NOW()) ON DUPLICATE KEY UPDATE source='manual', command_id=VALUES(command_id), content_id=VALUES(content_id), applied_at=VALUES(applied_at), expires_at=VALUES(expires_at), updated_at=NOW()");
    $stmt->bindValue(':screen_id', $screenId, PDO::PARAM_INT);
    $stmt->bindValue(':command_id', $commandId, PDO::PARAM_INT);
    $stmt->bindValue(':content_id', $contentId, PDO::PARAM_INT);
    $stmt->bindValue(':minutes', $durationMinutes, PDO::PARAM_INT);
    $stmt->execute();
}

function screenClearManualState(PDO $pdo, int $screenId): void
{
    $stmt = $pdo->prepare("UPDATE screen_state SET source='schedule', command_id=NULL, expires_at=NULL, updated_at=NOW() WHERE screen_id = :screen_id");
    $stmt->execute(['screen_id' => $screenId]);
}
